<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Equipo;
use App\Models\EstadisticaEquipoUsuario;
use App\Models\EstadisticaTorneoUsuario;
use App\Models\GolTorneoPartido;
use App\Models\Torneo;
use App\Models\TorneoPartido;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Controlador que agrupa la logica de torneo en la API.
 */
class TorneoController extends Controller
{
    /**
     * Devuelve el listado principal de recursos.
     */
    public function index()
    {
        return response()->json(
            Torneo::with('organizador')
                ->withCount(['equipos as equipos_count' => fn ($q) => $q->where('torneo_equipos.estado_inscripcion', 'aceptada')])
                ->withCount('partidosBracket')
                ->orderByDesc('id_torneo')
                ->get()
        );
    }

    /**
     * Guarda un nuevo recurso con los datos recibidos.
     */
    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre_torneo' => 'required|string|max:120',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'required|date|after_or_equal:today',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'tipo_torneo' => 'nullable|in:eliminatoria',
            'tipo_futbol' => 'required|in:5v5,7v7,11v11',
            'max_equipos' => 'required|in:4,8,16',
            'privacidad' => 'required|in:publico,privado',
            'codigo_acceso' => 'nullable|string|max:30',
            'cuota_inscripcion' => 'nullable|numeric|min:0',
            'premio' => 'nullable|string|max:160',
            'nombre_lugar' => 'nullable|string|max:160',
            'direccion' => 'nullable|string|max:220',
            'ciudad' => 'nullable|string|max:120',
            'provincia' => 'nullable|string|max:120',
            'latitud' => 'nullable|numeric|between:-90,90',
            'longitud' => 'nullable|numeric|between:-180,180',
        ]);

        if ($datos['privacidad'] === 'privado' && empty($datos['codigo_acceso'])) {
            return response()->json(['mensaje' => 'Los torneos privados necesitan codigo de acceso'], 422);
        }

        $datos['tipo_torneo'] = 'eliminatoria';
        $datos['id_organizador'] = $request->user()->id_usuario;
        $datos['estado_torneo'] = 'inscripcion_abierta';
        $datos['estado'] = 'inscripcion_abierta';
        $datos['codigo_acceso'] = $datos['privacidad'] === 'privado' ? strtoupper($datos['codigo_acceso']) : null;

        return response()->json(Torneo::create($datos)->load('organizador'), 201);
    }

    /**
     * Devuelve el detalle del recurso solicitado.
     */
    public function show($id)
    {
        return response()->json(
            Torneo::with([
                'organizador',
                'equipos.usuarios',
                'partidosBracket.equipoLocal.usuarios',
                'partidosBracket.equipoVisitante.usuarios',
                'partidosBracket.ganador',
                'partidosBracket.goles.usuario',
                'partidosBracket.goles.equipo',
            ])->withCount(['equipos as equipos_count' => fn ($q) => $q->where('torneo_equipos.estado_inscripcion', 'aceptada')])
                ->withCount('partidosBracket')
                ->findOrFail($id)
        );
    }

    /**
     * Actualiza los datos del recurso indicado.
     */
    public function update(Request $request, $id)
    {
        $torneo = Torneo::findOrFail($id);
        $this->autorizarOrganizador($request, $torneo);
        $torneo->update($request->all());

        return response()->json($torneo);
    }

    /**
     * Elimina el recurso indicado cuando el usuario tiene permiso.
     */
    public function destroy(Request $request, $id)
    {
        $torneo = Torneo::findOrFail($id);
        $this->autorizarOrganizador($request, $torneo);
        $torneo->delete();

        return response()->json(['mensaje' => 'Torneo eliminado']);
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function unirse(Request $request, $id)
    {
        $datos = $request->validate([
            'id_equipo' => 'required|integer|exists:equipos,id_equipo',
            'codigo_acceso' => 'nullable|string'
        ]);

        $torneo = Torneo::withCount(['equipos as equipos_count' => fn ($q) => $q->where('torneo_equipos.estado_inscripcion', 'aceptada')])->findOrFail($id);
        $equipo = Equipo::with('usuarios')->findOrFail($datos['id_equipo']);

        if (($torneo->estado_torneo ?? $torneo->estado) !== 'inscripcion_abierta') {
            return response()->json(['mensaje' => 'El torneo no acepta inscripciones ahora mismo'], 422);
        }

        if ($torneo->fecha_inicio && Carbon::parse($torneo->fecha_inicio)->startOfDay()->lt(now()->startOfDay())) {
            return response()->json(['mensaje' => 'El torneo ya ha empezado'], 422);
        }

        if ($torneo->privacidad === 'privado' && strtoupper($datos['codigo_acceso'] ?? '') !== strtoupper($torneo->codigo_acceso ?? '')) {
            return response()->json(['mensaje' => 'Código de acceso incorrecto'], 403);
        }

        if ($torneo->equipos_count >= (int) $torneo->max_equipos) {
            return response()->json(['mensaje' => 'El torneo esta lleno'], 422);
        }

        $miembro = $equipo->usuarios()->where('usuarios.id_usuario', $request->user()->id_usuario)->first();
        $esCapitan = $miembro && in_array($miembro->pivot?->rol_en_equipo, ['capitan', 'creador'], true);
        $esCreador = (int) $equipo->id_creador === (int) $request->user()->id_usuario;

        if (!$miembro || (!$esCapitan && !$esCreador)) {
            return response()->json(['mensaje' => 'Solo el capitan del equipo puede inscribirlo'], 403);
        }

        if ($this->jugadoresActivosEquipo($equipo) < $this->jugadoresMinimosEquipo($torneo->tipo_futbol)) {
            return response()->json(['mensaje' => 'Tu equipo no tiene jugadores suficientes para este torneo'], 422);
        }

        if ($torneo->equipos()->where('equipos.id_equipo', $equipo->id_equipo)->exists()) {
            return response()->json(['mensaje' => 'Este equipo ya esta inscrito en el torneo'], 422);
        }

        $torneo->equipos()->attach($equipo->id_equipo, [
            'estado_inscripcion' => 'aceptada',
            'fecha_inscripcion' => now(),
        ]);

        return response()->json(['mensaje' => 'Equipo inscrito correctamente']);
    }

    /**
     * Gestiona informacion relacionada con equipos.
     */
    public function inscribirEquipo(Request $request, $id)
    {
        return $this->unirse($request, $id);
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function iniciar(Request $request, $id)
    {
        $torneo = Torneo::with(['equipos' => fn ($q) => $q->where('torneo_equipos.estado_inscripcion', 'aceptada')])->findOrFail($id);
        $this->autorizarOrganizador($request, $torneo);

        if (($torneo->estado_torneo ?? $torneo->estado) !== 'inscripcion_abierta') {
            return response()->json(['mensaje' => 'El torneo ya esta iniciado o cerrado'], 422);
        }

        if ($torneo->partidosBracket()->exists()) {
            return response()->json(['mensaje' => 'Los brackets ya están generados'], 422);
        }

        $equipos = $torneo->equipos->values();
        if ($equipos->count() < 4 || !in_array($equipos->count(), [4, 8, 16], true)) {
            return response()->json(['mensaje' => 'Necesitas 4, 8 o 16 equipos inscritos'], 422);
        }

        DB::transaction(function () use ($torneo, $equipos) {
            $rondas = $this->rondasPorEquipos($equipos->count());

            foreach ($rondas as $indiceRonda => $ronda) {
                $partidosRonda = (int) ($equipos->count() / (2 ** ($indiceRonda + 1)));

                for ($i = 0; $i < $partidosRonda; $i++) {
                    $local = null;
                    $visitante = null;

                    if ($indiceRonda === 0) {
                        $local = $equipos[$i * 2]->id_equipo;
                        $visitante = $equipos[$i * 2 + 1]->id_equipo;
                    }

                    TorneoPartido::create([
                        'id_torneo' => $torneo->id_torneo,
                        'ronda' => $ronda,
                        'id_equipo_local' => $local,
                        'id_equipo_visitante' => $visitante,
                    ]);
                }
            }

            $torneo->estado_torneo = 'en_curso';
            $torneo->estado = 'en_curso';
            $torneo->save();
        });

        return response()->json(['mensaje' => 'Torneo iniciado correctamente']);
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function brackets($id)
    {
        return response()->json(
            TorneoPartido::with(['equipoLocal.usuarios', 'equipoVisitante.usuarios', 'ganador', 'goles.usuario', 'goles.equipo'])
                ->where('id_torneo', $id)
                ->orderByRaw("FIELD(ronda, 'Octavos', 'Cuartos', 'Semifinales', 'Final')")
                ->orderBy('id_torneo_partido')
                ->get()
        );
    }

    /**
     * Calcula y devuelve datos de ranking.
     */
    public function rankingGoles($id)
    {
        return response()->json($this->ranking($id, 'goles'));
    }

    /**
     * Calcula y devuelve datos de ranking.
     */
    public function rankingPorterias($id)
    {
        return response()->json($this->ranking($id, 'porterias_cero'));
    }

    /**
     * Gestiona informacion relacionada con partidos.
     */
    public function resultadoPartido(Request $request, $id)
    {
        $datos = $request->validate([
            'goles_local' => 'nullable|integer|min:0',
            'goles_visitante' => 'nullable|integer|min:0',
            'estadisticas' => 'nullable|array',
            'estadisticas.*.id_usuario' => 'required_with:estadisticas|integer|exists:usuarios,id_usuario',
            'estadisticas.*.id_equipo' => 'required_with:estadisticas|integer|exists:equipos,id_equipo',
            'estadisticas.*.goles' => 'nullable|integer|min:0',
            'estadisticas.*.porterias_cero' => 'nullable|integer|min:0',
        ]);

        $partido = TorneoPartido::with(['torneo', 'goles'])->findOrFail($id);
        $this->autorizarOrganizador($request, $partido->torneo);

        if ($partido->estado === 'jugado') {
            return response()->json(['mensaje' => 'Este resultado ya esta registrado'], 422);
        }

        if (!$partido->id_equipo_local || !$partido->id_equipo_visitante) {
            return response()->json(['mensaje' => 'Este cruce todavía no tiene los dos equipos'], 422);
        }

        $golesLocal = array_key_exists('goles_local', $datos) && $datos['goles_local'] !== null
            ? (int) $datos['goles_local']
            : $partido->goles->where('id_equipo', $partido->id_equipo_local)->count();
        $golesVisitante = array_key_exists('goles_visitante', $datos) && $datos['goles_visitante'] !== null
            ? (int) $datos['goles_visitante']
            : $partido->goles->where('id_equipo', $partido->id_equipo_visitante)->count();

        if ($golesLocal === $golesVisitante) {
            return response()->json(['mensaje' => 'En eliminatoria no puede haber empate'], 422);
        }

        $ganador = $golesLocal > $golesVisitante
            ? $partido->id_equipo_local
            : $partido->id_equipo_visitante;

        $partido->update([
            'goles_local' => $golesLocal,
            'goles_visitante' => $golesVisitante,
            'id_equipo_ganador' => $ganador,
            'estado' => 'jugado',
        ]);

        $this->avanzarGanador($partido, $ganador);
        $this->recalcularEstadisticasPartido($partido->fresh(['goles']));

        return response()->json(['mensaje' => 'Resultado guardado', 'partido' => $partido->fresh(['equipoLocal', 'equipoVisitante', 'ganador'])]);
    }

    /**
     * Guarda un nuevo recurso con los datos recibidos.
     */
    public function guardarGol(Request $request, $id)
    {
        $datos = $request->validate([
            'id_usuario' => 'required|integer|exists:usuarios,id_usuario',
            'id_equipo' => 'required|integer|exists:equipos,id_equipo',
            'minuto' => 'nullable|integer|min:1|max:130',
        ]);

        $partido = TorneoPartido::with(['torneo', 'equipoLocal.usuarios', 'equipoVisitante.usuarios'])->findOrFail($id);
        $this->autorizarOrganizador($request, $partido->torneo);

        if (($partido->torneo->estado_torneo ?? $partido->torneo->estado) === 'finalizado' && $request->user()->rol !== 'admin') {
            return response()->json(['mensaje' => 'No se puede editar un torneo finalizado'], 422);
        }

        if (!in_array((int) $datos['id_equipo'], [(int) $partido->id_equipo_local, (int) $partido->id_equipo_visitante], true)) {
            return response()->json(['mensaje' => 'Ese equipo no juega este partido'], 422);
        }

        $equipo = (int) $datos['id_equipo'] === (int) $partido->id_equipo_local ? $partido->equipoLocal : $partido->equipoVisitante;
        $pertenece = $equipo?->usuarios()
            ->where('usuarios.id_usuario', $datos['id_usuario'])
            ->where(function ($query) {
                $query->where('equipo_usuarios.estado', 'activo')->orWhereNull('equipo_usuarios.estado');
            })
            ->exists();

        if (!$pertenece) {
            return response()->json(['mensaje' => 'El goleador no pertenece a ese equipo'], 422);
        }

        $gol = GolTorneoPartido::create([
            'id_torneo_partido' => $partido->id_torneo_partido,
            'id_torneo' => $partido->id_torneo,
            'id_usuario' => $datos['id_usuario'],
            'id_equipo' => $datos['id_equipo'],
            'minuto' => $datos['minuto'] ?? null,
        ]);

        $this->sincronizarMarcadorDesdeGoles($partido);

        return response()->json($gol->load(['usuario', 'equipo']), 201);
    }

    /**
     * Elimina el recurso indicado cuando el usuario tiene permiso.
     */
    public function eliminarGol(Request $request, $id)
    {
        $gol = GolTorneoPartido::with('partido.torneo')->findOrFail($id);
        $this->autorizarOrganizador($request, $gol->partido->torneo);

        if ($gol->partido->estado === 'jugado') {
            return response()->json(['mensaje' => 'No puedes quitar goles de un resultado ya confirmado'], 422);
        }

        $partido = $gol->partido;
        $gol->delete();
        $this->sincronizarMarcadorDesdeGoles($partido);

        return response()->json(['mensaje' => 'Gol eliminado']);
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    private function autorizarOrganizador(Request $request, Torneo $torneo): void
    {
        if ($request->user()->rol !== 'admin' && (int) $torneo->id_organizador !== (int) $request->user()->id_usuario) {
            abort(response()->json(['mensaje' => 'Solo el organizador o admin puede hacer esto'], 403));
        }
    }

    /**
     * Gestiona informacion relacionada con equipos.
     */
    private function jugadoresActivosEquipo(Equipo $equipo): int
    {
        $consulta = $equipo->usuarios();

        if (Schema::hasColumn('equipo_usuarios', 'estado')) {
            $consulta->where(function ($q) {
                $q->where('equipo_usuarios.estado', 'activo')->orWhereNull('equipo_usuarios.estado');
            });
        }

        return $consulta->count();
    }

    /**
     * Gestiona informacion relacionada con equipos.
     */
    private function jugadoresMinimosEquipo(string $tipo): int
    {
        return str_contains($tipo, '5') ? 5 : (str_contains($tipo, '7') ? 7 : 11);
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    private function avanzarGanador(TorneoPartido $partido, int $ganador): void
    {
        $siguiente = match ($partido->ronda) {
            'Octavos' => 'Cuartos',
            'Cuartos' => 'Semifinales',
            'Semifinales' => 'Final',
            default => null,
        };

        if (!$siguiente) {
            $partido->torneo->update(['estado_torneo' => 'finalizado', 'estado' => 'finalizado']);
            return;
        }

        $partidosRonda = TorneoPartido::where('id_torneo', $partido->id_torneo)
            ->where('ronda', $partido->ronda)
            ->orderBy('id_torneo_partido')
            ->pluck('id_torneo_partido')
            ->values();
        $indiceActual = max(0, $partidosRonda->search($partido->id_torneo_partido));
        $indiceSiguiente = (int) floor($indiceActual / 2);

        $slot = TorneoPartido::where('id_torneo', $partido->id_torneo)
            ->where('ronda', $siguiente)
            ->orderBy('id_torneo_partido')
            ->skip($indiceSiguiente)
            ->first();

        if (!$slot) {
            $slot = TorneoPartido::create(['id_torneo' => $partido->id_torneo, 'ronda' => $siguiente]);
        }

        $slot->update($indiceActual % 2 === 0 ? ['id_equipo_local' => $ganador] : ['id_equipo_visitante' => $ganador]);
    }

    /**
     * Gestiona informacion relacionada con equipos.
     */
    private function rondasPorEquipos(int $equipos): array
    {
        return match ($equipos) {
            16 => ['Octavos', 'Cuartos', 'Semifinales', 'Final'],
            8 => ['Cuartos', 'Semifinales', 'Final'],
            default => ['Semifinales', 'Final'],
        };
    }

    /**
     * Calcula y devuelve datos de ranking.
     */
    private function ranking(int $idTorneo, string $campo)
    {
        return EstadisticaTorneoUsuario::with(['usuario', 'equipo'])
            ->where('id_torneo', $idTorneo)
            ->orderByDesc($campo)
            ->limit(10)
            ->get();
    }

    /**
     * Gestiona goles registrados.
     */
    private function sincronizarMarcadorDesdeGoles(TorneoPartido $partido): void
    {
        $partido->load('goles');
        $partido->update([
            'goles_local' => $partido->goles->where('id_equipo', $partido->id_equipo_local)->count(),
            'goles_visitante' => $partido->goles->where('id_equipo', $partido->id_equipo_visitante)->count(),
        ]);
    }

    /**
     * Gestiona informacion relacionada con partidos.
     */
    private function recalcularEstadisticasPartido(TorneoPartido $partido): void
    {
        $this->sumarPartidoJugadoEquipo($partido->id_equipo_local);
        $this->sumarPartidoJugadoEquipo($partido->id_equipo_visitante);

        foreach ($partido->goles->groupBy('id_usuario') as $idUsuario => $golesUsuario) {
            $registro = EstadisticaTorneoUsuario::firstOrCreate(
                [
                    'id_torneo' => $partido->id_torneo,
                    'id_usuario' => $idUsuario,
                    'id_equipo' => $golesUsuario->first()->id_equipo,
                ],
                [
                    'goles' => 0,
                    'asistencias' => 0,
                    'porterias_cero' => 0,
                    'partidos_jugados' => 0,
                ]
            );

            $registro->increment('goles', $golesUsuario->count());

            $registroEquipo = EstadisticaEquipoUsuario::firstOrCreate(
                [
                    'id_equipo' => $golesUsuario->first()->id_equipo,
                    'id_usuario' => $idUsuario,
                ],
                [
                    'partidos_jugados' => 0,
                    'goles' => 0,
                    'asistencias' => 0,
                    'porterias_cero' => 0,
                ]
            );

            $registroEquipo->increment('goles', $golesUsuario->count());
        }

        if ((int) $partido->goles_visitante === 0) {
            $this->sumarPorteriasEquipo($partido->id_torneo, $partido->id_equipo_local);
        }

        if ((int) $partido->goles_local === 0) {
            $this->sumarPorteriasEquipo($partido->id_torneo, $partido->id_equipo_visitante);
        }
    }

    /**
     * Gestiona informacion relacionada con partidos.
     */
    private function sumarPartidoJugadoEquipo(?int $idEquipo): void
    {
        if (!$idEquipo) {
            return;
        }

        $equipo = Equipo::with('usuarios')->find($idEquipo);
        foreach ($equipo?->usuarios ?? [] as $usuario) {
            $estado = $usuario->pivot?->estado ?? 'activo';
            $rol = $usuario->pivot?->rol_en_equipo ?? 'jugador';

            if ($estado !== 'activo' || $rol === 'invitado') {
                continue;
            }

            $registro = EstadisticaEquipoUsuario::firstOrCreate(
                [
                    'id_equipo' => $idEquipo,
                    'id_usuario' => $usuario->id_usuario,
                ],
                [
                    'partidos_jugados' => 0,
                    'goles' => 0,
                    'asistencias' => 0,
                    'porterias_cero' => 0,
                ]
            );

            $registro->increment('partidos_jugados');
        }
    }

    /**
     * Gestiona informacion relacionada con equipos.
     */
    private function sumarPorteriasEquipo(int $idTorneo, ?int $idEquipo): void
    {
        if (!$idEquipo) {
            return;
        }

        $equipo = Equipo::with('usuarios')->find($idEquipo);
        foreach ($equipo?->usuarios ?? [] as $usuario) {
            if (!$this->puedeSumarPorteriaCero($usuario->posiciones_favoritas ?? '')) {
                continue;
            }

            $registro = EstadisticaTorneoUsuario::firstOrCreate(
                [
                    'id_torneo' => $idTorneo,
                    'id_usuario' => $usuario->id_usuario,
                    'id_equipo' => $idEquipo,
                ],
                [
                    'goles' => 0,
                    'asistencias' => 0,
                    'porterias_cero' => 0,
                    'partidos_jugados' => 0,
                ]
            );

            $registro->increment('porterias_cero');
        }
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    private function puedeSumarPorteriaCero(string $posiciones): bool
    {
        $posiciones = mb_strtolower($posiciones);

        return str_contains($posiciones, 'portero')
            || str_contains($posiciones, 'porter')
            || str_contains($posiciones, 'defensa')
            || str_contains($posiciones, 'defens');
    }
}

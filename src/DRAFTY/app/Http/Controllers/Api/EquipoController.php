<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Amistad;
use App\Models\EstadisticaEquipoUsuario;
use App\Models\Equipo;
use App\Models\MensajeEquipo;
use App\Models\Partido;
use App\Models\Torneo;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Controlador que agrupa la logica de equipo en la API.
 */
class EquipoController extends Controller
{
    /**
     * Devuelve el listado principal de recursos.
     */
    public function index()
    {
        return response()->json(
            Equipo::withCount(['usuarios as jugadores_count' => function ($query) {
                    $this->soloMiembrosActivos($query);
                }])
                ->when(Schema::hasColumn('equipos', 'privacidad'), function ($query) {
                    $query->where(function ($subquery) {
                        $subquery->where('privacidad', 'publico')
                            ->orWhereNull('privacidad');
                    });
                })
                ->orderBy('nombre_equipo')
                ->get()
        );
    }

    /**
     * Guarda un nuevo recurso con los datos recibidos.
     */
    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre_equipo' => 'required|string',
            'descripcion' => 'nullable|string',
            'privacidad' => 'nullable|in:publico,privado'
        ]);

        if (Schema::hasColumn('equipos', 'privacidad')) {
            $datos['privacidad'] = $datos['privacidad'] ?? 'publico';
        } else {
            unset($datos['privacidad']);
        }
        $datos['id_creador'] = $request->user()->id_usuario;
        $datos['fecha_creacion'] = now()->toDateString();

        $equipo = Equipo::create($datos);
        $equipo->usuarios()->attach($request->user()->id_usuario, $this->datosMiembro('capitan'));
        $this->crearEstadisticaMiembro($equipo->id_equipo, $request->user()->id_usuario);

        return response()->json($equipo->loadCount('usuarios'), 201);
    }

    /**
     * Gestiona informacion relacionada con equipos.
     */
    public function misEquipos(Request $request)
    {
        return response()->json(
            $request->user()
                ->equipos()
                ->where(function ($query) {
                    $query->where('equipo_usuarios.rol_en_equipo', '!=', 'invitado')
                        ->orWhereNull('equipo_usuarios.rol_en_equipo');
                })
                ->when(Schema::hasColumn('equipo_usuarios', 'estado'), function ($query) {
                    $query->where(function ($subquery) {
                        $subquery->where('equipo_usuarios.estado', 'activo')
                            ->orWhereNull('equipo_usuarios.estado');
                    });
                })
                ->with(['usuarios', 'creador'])
                ->withCount(['usuarios as jugadores_count' => function ($query) {
                    $this->soloMiembrosActivos($query);
                }])
                ->orderBy('fecha_creacion', 'desc')
                ->get()
        );
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function unirse(Request $request, $id)
    {
        $equipo = Equipo::findOrFail($id);
        $usuario = $request->user();

        $miembroExistente = $equipo->usuarios()
            ->where('usuarios.id_usuario', $usuario->id_usuario)
            ->first();

        if ($miembroExistente) {
            $estadoMiembro = $miembroExistente->pivot->estado ?? 'activo';

            if ($miembroExistente->pivot->rol_en_equipo === 'invitado' || $estadoMiembro !== 'activo') {
                $equipo->usuarios()->updateExistingPivot($usuario->id_usuario, $this->datosMiembro('jugador'));
                $this->crearEstadisticaMiembro($equipo->id_equipo, $usuario->id_usuario);

                return response()->json([
                    'mensaje' => 'Te has unido al equipo correctamente',
                    'equipo' => $equipo,
                    'usuario' => [
                        'id_usuario' => $usuario->id_usuario,
                        'nombre_usuario' => $usuario->nombre_usuario,
                    ],
                ]);
            }

            return response()->json([
                'mensaje' => 'Ya estás en este equipo con el usuario ' . $usuario->nombre_usuario,
                'usuario' => [
                    'id_usuario' => $usuario->id_usuario,
                    'nombre_usuario' => $usuario->nombre_usuario,
                ],
            ]);
        }

        if (($equipo->privacidad ?? 'publico') === 'privado') {
            return response()->json([
                'mensaje' => 'Este equipo es privado. Solo puedes entrar mediante una invitación de equipo.'
            ], 403);
        }

        $equipo->usuarios()->attach($usuario->id_usuario, $this->datosMiembro('jugador'));
        $this->crearEstadisticaMiembro($equipo->id_equipo, $usuario->id_usuario);

        return response()->json([
            'mensaje' => 'Te has unido al equipo correctamente',
            'equipo' => $equipo,
            'usuario' => [
                'id_usuario' => $usuario->id_usuario,
                'nombre_usuario' => $usuario->nombre_usuario,
            ],
        ]);
    }

    /**
     * Gestiona datos relacionados con amigos y solicitudes.
     */
    public function invitarAmigo(Request $request, $id, $idUsuario)
    {
        $equipo = Equipo::findOrFail($id);
        $usuario = $request->user();
        $amigo = Usuario::findOrFail($idUsuario);

        if (!$this->sonAmigos($usuario->id_usuario, $amigo->id_usuario)) {
            return response()->json([
                'mensaje' => 'Solo puedes invitar a usuarios que ya son tus amigos'
            ], 403);
        }

        $perteneceAlEquipo = $equipo->usuarios()
            ->where('usuarios.id_usuario', $usuario->id_usuario)
            ->where(fn ($query) => $this->soloMiembrosActivos($query))
            ->exists();

        if (!$perteneceAlEquipo) {
            return response()->json([
                'mensaje' => 'Tienes que pertenecer al equipo para invitar a un amigo'
            ], 403);
        }

        $amigoYaEsta = $equipo->usuarios()
            ->where('usuarios.id_usuario', $amigo->id_usuario)
            ->first();

        if ($amigoYaEsta) {
            return response()->json([
                'mensaje' => $amigoYaEsta->pivot->rol_en_equipo === 'invitado'
                    ? 'Tu amigo ya tiene una invitación pendiente para este equipo'
                    : 'Tu amigo ya está en este equipo',
                'equipo' => $equipo
            ]);
        }

        $equipo->usuarios()->attach($amigo->id_usuario, $this->datosMiembro('invitado', 'pendiente'));

        return response()->json([
            'mensaje' => 'Invitación a equipo enviada correctamente',
            'equipo' => $equipo->load('usuarios')
        ], 201);
    }

    /**
     * Gestiona una invitacion enviada o recibida por el usuario.
     */
    public function invitaciones(Request $request)
    {
        return response()->json(
            $request->user()
                ->equipos()
                ->wherePivot('rol_en_equipo', 'invitado')
                ->with('usuarios')
                ->get()
        );
    }

    /**
     * Gestiona una invitacion enviada o recibida por el usuario.
     */
    public function aceptarInvitacion(Request $request, $id)
    {
        $equipo = Equipo::findOrFail($id);
        $usuario = $request->user();

        $invitacion = $equipo->usuarios()
            ->where('usuarios.id_usuario', $usuario->id_usuario)
            ->wherePivot('rol_en_equipo', 'invitado')
            ->exists();

        if (!$invitacion) {
            return response()->json(['mensaje' => 'No tienes una invitación pendiente para este equipo'], 404);
        }

        $equipo->usuarios()->updateExistingPivot($usuario->id_usuario, $this->datosMiembro('jugador', 'activo', true));
        $this->crearEstadisticaMiembro($equipo->id_equipo, $usuario->id_usuario);

        return response()->json(['mensaje' => 'Invitación aceptada']);
    }

    /**
     * Gestiona una invitacion enviada o recibida por el usuario.
     */
    public function rechazarInvitacion(Request $request, $id)
    {
        $equipo = Equipo::findOrFail($id);
        $usuario = $request->user();

        $invitacion = $equipo->usuarios()
            ->where('usuarios.id_usuario', $usuario->id_usuario)
            ->wherePivot('rol_en_equipo', 'invitado')
            ->exists();

        if (!$invitacion) {
            return response()->json(['mensaje' => 'No tienes una invitación pendiente para este equipo'], 404);
        }

        $equipo->usuarios()->detach($usuario->id_usuario);

        return response()->json(['mensaje' => 'Invitación rechazada']);
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function salir(Request $request, $id)
    {
        $equipo = Equipo::with('usuarios')->findOrFail($id);
        $usuario = $request->user();

        $perteneceAlEquipo = $equipo->usuarios()
            ->where('usuarios.id_usuario', $usuario->id_usuario)
            ->where(fn ($query) => $this->soloMiembrosActivos($query))
            ->exists();

        if (!$perteneceAlEquipo) {
            return response()->json([
                'mensaje' => 'No estás en este equipo'
            ], 400);
        }

        $equipo->usuarios()->detach($usuario->id_usuario);

        if (!$this->tieneMiembrosActivos($equipo)) {
            $equipo->delete();

            return response()->json([
                'mensaje' => 'Has salido del equipo. El equipo se ha eliminado porque no quedaban miembros'
            ]);
        }

        $tieneCapitan = $equipo->usuarios()
            ->where(fn ($query) => $this->soloMiembrosActivos($query))
            ->wherePivot('rol_en_equipo', 'capitan')
            ->exists();

        if (!$tieneCapitan) {
            $nuevoCapitan = $equipo->usuarios()
                ->where(fn ($query) => $this->soloMiembrosActivos($query))
                ->first();

            if ($nuevoCapitan) {
                $equipo->usuarios()->updateExistingPivot($nuevoCapitan->id_usuario, [
                    'rol_en_equipo' => 'capitan'
                ]);
            }
        }

        return response()->json([
            'mensaje' => 'Has salido del equipo'
        ]);
    }

    /**
     * Devuelve el detalle del recurso solicitado.
     */
    public function show($id)
    {
        $equipo = Equipo::with([
                'creador',
                'usuarios',
                'partidosLocal.campo',
                'partidosVisitante.campo',
                'estadisticasUsuarios.usuario'
            ])
            ->withCount(['usuarios as jugadores_count' => function ($query) {
                $this->soloMiembrosActivos($query);
            }])
            ->findOrFail($id);

        if (!$this->perteneceAlEquipo(request(), $equipo)) {
            return response()->json(['mensaje' => 'No puedes ver este equipo porque no perteneces a él'], 403);
        }

        return response()->json($equipo);
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function jugadores(Request $request, $id)
    {
        $equipo = Equipo::findOrFail($id);

        if (!$this->perteneceAlEquipo($request, $equipo)) {
            return response()->json(['mensaje' => 'No puedes ver los jugadores de este equipo'], 403);
        }

        return response()->json(
            $equipo->usuarios()
                ->where(fn ($query) => $this->soloMiembrosActivos($query))
                ->orderBy('nombre_usuario')
                ->get()
        );
    }

    /**
     * Gestiona informacion relacionada con partidos.
     */
    public function partidos(Request $request, $id)
    {
        $equipo = Equipo::findOrFail($id);

        if (!$this->perteneceAlEquipo($request, $equipo)) {
            return response()->json(['mensaje' => 'No puedes ver los partidos de este equipo'], 403);
        }

        return response()->json(
            Partido::with('campo')
                ->withCount(['usuarios' => fn ($query) => $query->where('participantes_partido.estado_participacion', 'confirmado')])
                ->where(function ($query) use ($id) {
                    $query->where('id_equipo_local', $id)
                        ->orWhere('id_equipo_visitante', $id);
                })
                ->where(function ($query) {
                    $query->whereNotIn('estado', ['finalizado', 'cancelado'])
                        ->orWhereNull('estado');
                })
                ->orderBy('fecha')
                ->orderBy('hora')
                ->get()
        );
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function historial(Request $request, $id)
    {
        $equipo = Equipo::findOrFail($id);

        if (!$this->perteneceAlEquipo($request, $equipo)) {
            return response()->json(['mensaje' => 'No puedes ver el historial de este equipo'], 403);
        }

        return response()->json(
            Partido::with('campo')
                ->withCount(['usuarios' => fn ($query) => $query->where('participantes_partido.estado_participacion', 'confirmado')])
                ->where(function ($query) use ($id) {
                    $query->where('id_equipo_local', $id)
                        ->orWhere('id_equipo_visitante', $id);
                })
                ->where(function ($query) {
                    $query->whereIn('estado', ['finalizado', 'cancelado'])
                        ->orWhereNotNull('goles_equipo_a')
                        ->orWhereNotNull('goles_equipo_b');
                })
                ->orderByDesc('fecha')
                ->orderByDesc('hora')
                ->get()
        );
    }

    /**
     * Calcula y devuelve datos de ranking.
     */
    public function ranking(Request $request, $id)
    {
        $equipo = Equipo::findOrFail($id);

        if (!$this->perteneceAlEquipo($request, $equipo)) {
            return response()->json(['mensaje' => 'No puedes ver el ranking de este equipo'], 403);
        }

        return response()->json(
            EstadisticaEquipoUsuario::with('usuario')
                ->where('id_equipo', $id)
                ->orderByDesc('goles')
                ->orderByDesc('asistencias')
                ->orderByDesc('porterias_cero')
                ->get()
        );
    }

    /**
     * Actualiza los datos del recurso indicado.
     */
    public function cambiarRolMiembro(Request $request, $id, $idUsuario)
    {
        $datos = $request->validate([
            'rol_en_equipo' => 'required|in:capitan,jugador',
        ]);

        $equipo = Equipo::findOrFail($id);

        if (!$this->puedeGestionarEquipo($request, $equipo)) {
            return response()->json(['mensaje' => 'Solo un capitán puede editar roles del equipo'], 403);
        }

        if ((int) $equipo->id_creador === (int) $idUsuario) {
            return response()->json(['mensaje' => 'No se puede editar el rol del creador del equipo'], 403);
        }

        $miembro = $equipo->usuarios()
            ->where('usuarios.id_usuario', $idUsuario)
            ->where(fn ($query) => $this->soloMiembrosActivos($query))
            ->exists();

        if (!$miembro) {
            return response()->json(['mensaje' => 'Ese usuario no pertenece al equipo'], 404);
        }

        $equipo->usuarios()->updateExistingPivot($idUsuario, [
            'rol_en_equipo' => $datos['rol_en_equipo'],
        ]);

        return response()->json(['mensaje' => 'Rol actualizado correctamente']);
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function expulsarMiembro(Request $request, $id, $idUsuario)
    {
        $equipo = Equipo::findOrFail($id);

        if (!$this->puedeGestionarEquipo($request, $equipo)) {
            return response()->json(['mensaje' => 'Solo un capitán puede expulsar jugadores'], 403);
        }

        if ((int) $equipo->id_creador === (int) $idUsuario) {
            return response()->json(['mensaje' => 'No se puede expulsar al creador del equipo'], 403);
        }

        if ((int) $request->user()->id_usuario === (int) $idUsuario) {
            return response()->json(['mensaje' => 'Usa abandonar equipo para salir tú mismo'], 422);
        }

        $miembro = $equipo->usuarios()
            ->where('usuarios.id_usuario', $idUsuario)
            ->where(fn ($query) => $this->soloMiembrosActivos($query))
            ->exists();

        if (!$miembro) {
            return response()->json(['mensaje' => 'Ese usuario no pertenece al equipo'], 404);
        }

        $equipo->usuarios()->detach($idUsuario);

        return response()->json(['mensaje' => 'Jugador expulsado del equipo']);
    }

    /**
     * Gestiona informacion relacionada con torneos.
     */
    public function torneosGanados(Request $request, $id)
    {
        $equipo = Equipo::findOrFail($id);

        if (!$this->perteneceAlEquipo($request, $equipo)) {
            return response()->json(['mensaje' => 'No puedes ver los torneos de este equipo'], 403);
        }

        return response()->json(
            Torneo::with([
                    'partidosBracket' => function ($query) {
                        $query->where('estado', 'jugado')
                            ->with(['equipoLocal', 'equipoVisitante', 'ganador']);
                    }
                ])
                ->whereHas('partidosBracket', function ($query) use ($id) {
                    $query->where('estado', 'jugado')
                        ->where(function ($subquery) use ($id) {
                            $subquery->where('id_equipo_local', $id)
                                ->orWhere('id_equipo_visitante', $id);
                        });
                })
                ->orderByDesc('fecha_fin')
                ->orderByDesc('fecha_inicio')
                ->get()
                ->map(function ($torneo) use ($id) {
                    $partidosEquipo = $torneo->partidosBracket
                        ->filter(fn ($partido) => (int) $partido->id_equipo_local === (int) $id || (int) $partido->id_equipo_visitante === (int) $id)
                        ->values();
                    $torneo->setRelation('partidosBracket', $partidosEquipo);
                    $torneo->campeon = $torneo->partidosBracket
                        ->contains(fn ($partido) => $partido->ronda === 'Final' && (int) $partido->id_equipo_ganador === (int) $id);

                    return $torneo;
                })
        );
    }

    /**
     * Gestiona mensajes del chat.
     */
    public function mensajes(Request $request, $id)
    {
        $equipo = Equipo::findOrFail($id);

        if (!$this->perteneceAlEquipo($request, $equipo)) {
            return response()->json(['mensaje' => 'No puedes ver el chat de este equipo'], 403);
        }

        return response()->json(
            MensajeEquipo::with('usuario')
                ->where('id_equipo', $id)
                ->orderBy('created_at')
                ->get()
        );
    }

    /**
     * Gestiona mensajes del chat.
     */
    public function enviarMensaje(Request $request, $id)
    {
        $datos = $request->validate([
            'mensaje' => 'required|string|max:1000'
        ]);

        $equipo = Equipo::findOrFail($id);

        if (!$this->perteneceAlEquipo($request, $equipo)) {
            return response()->json(['mensaje' => 'No puedes escribir en el chat de este equipo'], 403);
        }

        $mensaje = MensajeEquipo::create([
            'id_equipo' => $equipo->id_equipo,
            'id_usuario' => $request->user()->id_usuario,
            'mensaje' => $datos['mensaje']
        ]);

        return response()->json($mensaje->load('usuario'), 201);
    }

    /**
     * Gestiona informacion relacionada con partidos.
     */
    public function unirseAPartido(Request $request, $id, $idPartido)
    {
        $equipo = Equipo::findOrFail($id);
        $partido = Partido::findOrFail($idPartido);
        $usuario = $request->user();

        if (!$this->perteneceAlEquipo($request, $equipo)) {
            return response()->json(['mensaje' => 'Tienes que pertenecer al equipo para unirte a este partido'], 403);
        }

        if ((int) $partido->id_equipo_local !== (int) $equipo->id_equipo && (int) $partido->id_equipo_visitante !== (int) $equipo->id_equipo) {
            return response()->json(['mensaje' => 'Este partido no pertenece a ese equipo'], 403);
        }

        if ($partido->estado === 'cancelado') {
            return response()->json(['mensaje' => 'Este partido está cancelado'], 400);
        }

        $participante = $partido->usuarios()
            ->where('usuarios.id_usuario', $usuario->id_usuario)
            ->first();

        if ($participante) {
            return response()->json([
                'mensaje' => 'Ya estás unido a este partido',
                'id_partido' => $partido->id_partido
            ], 400);
        }

        if ($partido->usuarios()->wherePivot('estado_participacion', 'confirmado')->count() >= $this->capacidadTotal($partido)) {
            return response()->json(['mensaje' => 'El partido ya está completo'], 400);
        }

        $partido->usuarios()->attach($usuario->id_usuario, [
            'estado_participacion' => 'confirmado',
            'equipo_asignado' => (int) $partido->id_equipo_local === (int) $equipo->id_equipo ? 'Equipo A' : 'Equipo B',
            'posicion_asignada' => $this->elegirPosicion($usuario->posiciones_favoritas, $partido),
            'es_capitan' => false
        ]);

        return response()->json([
            'mensaje' => 'Te has unido al partido del equipo correctamente',
            'id_partido' => $partido->id_partido
        ]);
    }

    /**
     * Actualiza los datos del recurso indicado.
     */
    public function update(Request $request, $id)
    {
        $equipo = Equipo::findOrFail($id);
        $equipo->update($request->all());

        return response()->json($equipo);
    }

    /**
     * Elimina el recurso indicado cuando el usuario tiene permiso.
     */
    public function destroy($id)
    {
        Equipo::findOrFail($id)->delete();
        return response()->json(['mensaje' => 'Equipo eliminado']);
    }

    /**
     * Gestiona datos relacionados con amigos y solicitudes.
     */
    private function sonAmigos(int $usuarioId, int $amigoId): bool
    {
        $columnaEmisor = Schema::hasColumn('amistades', 'id_usuario_emisor') ? 'id_usuario_emisor' : 'id_usuario';
        $columnaReceptor = Schema::hasColumn('amistades', 'id_usuario_receptor') ? 'id_usuario_receptor' : 'id_amigo';

        return Amistad::whereIn('estado', ['aceptada', 'aceptado'])
            ->where(function ($query) use ($usuarioId, $amigoId, $columnaEmisor, $columnaReceptor) {
                $query->where(function ($subquery) use ($usuarioId, $amigoId, $columnaEmisor, $columnaReceptor) {
                    $subquery->where($columnaEmisor, $usuarioId)
                        ->where($columnaReceptor, $amigoId);
                })->orWhere(function ($subquery) use ($usuarioId, $amigoId, $columnaEmisor, $columnaReceptor) {
                    $subquery->where($columnaEmisor, $amigoId)
                        ->where($columnaReceptor, $usuarioId);
                });
            })
            ->exists();
    }

    /**
     * Gestiona informacion relacionada con equipos.
     */
    private function perteneceAlEquipo(Request $request, Equipo $equipo): bool
    {
        return $equipo->usuarios()
            ->where('usuarios.id_usuario', $request->user()->id_usuario)
            ->where(fn ($query) => $this->soloMiembrosActivos($query))
            ->exists();
    }

    /**
     * Gestiona informacion relacionada con equipos.
     */
    private function puedeGestionarEquipo(Request $request, Equipo $equipo): bool
    {
        if ((int) $equipo->id_creador === (int) $request->user()->id_usuario) {
            return true;
        }

        return $equipo->usuarios()
            ->where('usuarios.id_usuario', $request->user()->id_usuario)
            ->wherePivot('rol_en_equipo', 'capitan')
            ->where(fn ($query) => $this->soloMiembrosActivos($query))
            ->exists();
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    private function soloMiembrosActivos($query): void
    {
        $query->where(function ($subquery) {
            $subquery->where('equipo_usuarios.rol_en_equipo', '!=', 'invitado')
                ->orWhereNull('equipo_usuarios.rol_en_equipo');
        });

        if (Schema::hasColumn('equipo_usuarios', 'estado')) {
            $query->where(function ($subquery) {
                $subquery->where('equipo_usuarios.estado', 'activo')
                    ->orWhereNull('equipo_usuarios.estado');
            });
        }
    }

    /**
     * Prepara los datos del pivote equipo_usuarios.
     *
     * Incluye el estado de la invitacion y el indicador de visto cuando
     * la base de datos tiene esas columnas.
     *
     * @param string $rol Rol del usuario dentro del equipo.
     * @param string $estado Estado del miembro o invitacion.
     * @param bool|null $vistoPorInvitado Valor explicito de visto para invitaciones.
     * @return array<string, mixed> Datos listos para attach/updateExistingPivot.
     */
    private function datosMiembro(string $rol, string $estado = 'activo', ?bool $vistoPorInvitado = null): array
    {
        $datos = ['rol_en_equipo' => $rol];

        if (Schema::hasColumn('equipo_usuarios', 'estado')) {
            $datos['estado'] = $estado;
        }

        if (Schema::hasColumn('equipo_usuarios', 'visto_por_invitado')) {
            $datos['visto_por_invitado'] = $vistoPorInvitado ?? $rol !== 'invitado';
        }

        return $datos;
    }

    /**
     * Guarda un nuevo recurso con los datos recibidos.
     */
    private function crearEstadisticaMiembro(int $idEquipo, int $idUsuario): void
    {
        if (!Schema::hasTable('estadisticas_equipo_usuario')) {
            return;
        }

        EstadisticaEquipoUsuario::firstOrCreate([
            'id_equipo' => $idEquipo,
            'id_usuario' => $idUsuario
        ]);
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    private function tieneMiembrosActivos(Equipo $equipo): bool
    {
        return $equipo->usuarios()
            ->where(fn ($query) => $this->soloMiembrosActivos($query))
            ->exists();
    }

    /**
     * Calcula un total usado por la respuesta.
     */
    private function capacidadTotal(Partido $partido): int
    {
        $tipo = strtolower($partido->tipo_futbol ?? '');

        if (str_contains($tipo, '5v5') || str_contains($tipo, '5') || str_contains($tipo, 'sala')) {
            $capacidadPorTipo = 14;
        } elseif (str_contains($tipo, '7')) {
            $capacidadPorTipo = 20;
        } else {
            $capacidadPorTipo = 26;
        }

        if (!empty($partido->plazas_totales)) {
            return max((int) $partido->plazas_totales, $capacidadPorTipo);
        }

        return $capacidadPorTipo;
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    private function elegirPosicion(?string $posicionesFavoritas, Partido $partido): string
    {
        $favoritas = array_map('trim', explode(',', $posicionesFavoritas ?? ''));
        $tipo = strtolower($partido->tipo_futbol ?? '');
        $esSala = str_contains($tipo, '5v5') || str_contains($tipo, '5') || str_contains($tipo, 'sala');

        foreach ($favoritas as $posicion) {
            if ($posicion === 'Portero') {
                return 'POR';
            }

            if ($posicion === 'Defensa') {
                return 'DFC';
            }

            if ($posicion === 'Mediocentro') {
                return $esSala ? 'ALA' : 'MC';
            }

            if ($posicion === 'Delantero') {
                return $esSala ? 'PIV' : 'DC';
            }
        }

        return $esSala ? 'ALA' : 'MC';
    }
}

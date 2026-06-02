<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Competitivo;
use App\Models\Estadistica;
use App\Models\GolPartido;
use App\Models\Partido;
use App\Models\ResultadoPartido;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ResultadoPartidoController extends Controller
{
    public function show($id)
    {
        $partido = Partido::with(['resultado.registrador', 'goles.usuario', 'usuarios'])->findOrFail($id);
        $this->comprobarCancelacionInterna($partido);

        return response()->json([
            'resultado' => $this->resultadoConGoles($partido),
            'ventana_abierta' => $this->ventanaResultadoAbierta($partido),
            'fecha_limite_resultado' => $this->fechaLimiteResultado($partido)?->toDateTimeString(),
        ]);
    }

    public function comprobarCancelacion($id)
    {
        $partido = Partido::with('usuarios')->findOrFail($id);
        $cancelado = $this->comprobarCancelacionInterna($partido);

        return response()->json([
            'cancelado' => $cancelado,
            'estado' => $partido->fresh()->estado,
            'jugadores_minimos' => $this->jugadoresMinimos($partido),
            'jugadores_confirmados' => $this->jugadoresConfirmados($partido),
        ]);
    }

    public function store(Request $request, $id)
    {
        $partido = Partido::with(['usuarios', 'goles'])->findOrFail($id);
        $this->comprobarCancelacionInterna($partido);

        if ($partido->estado === 'cancelado') {
            return response()->json(['mensaje' => 'No se puede registrar resultado de un partido cancelado'], 400);
        }

        if (!$this->ventanaResultadoAbierta($partido)) {
            return response()->json(['mensaje' => 'La ventana de resultado no esta abierta o ya ha terminado'], 403);
        }

        if (!$this->puedeRegistrarResultado($request, $partido)) {
            return response()->json(['mensaje' => 'No tienes permiso para registrar este resultado'], 403);
        }

        $golesLocal = $partido->goles()->where('equipo_sala', 'Equipo A')->count();
        $golesVisitante = $partido->goles()->where('equipo_sala', 'Equipo B')->count();
        $resultado = ResultadoPartido::updateOrCreate(
            ['id_partido' => $partido->id_partido],
            [
                'goles_local' => $golesLocal,
                'goles_visitante' => $golesVisitante,
                'registrado_por' => $request->user()->id_usuario,
                'tipo_registro' => 'capitan',
                'confirmado_local' => false,
                'confirmado_visitante' => false,
                'estado_resultado' => 'pendiente',
                'fecha_limite_resultado' => $this->fechaLimiteResultado($partido),
            ]
        );

        $partido->goles_equipo_a = $golesLocal;
        $partido->goles_equipo_b = $golesVisitante;
        $partido->fecha_limite_resultado = $this->fechaLimiteResultado($partido);
        $partido->save();

        return response()->json([
            'mensaje' => 'Resultado registrado correctamente',
            'resultado' => $resultado->fresh()
        ], 201);
    }

    public function confirmar(Request $request, $id)
    {
        $partido = Partido::with(['usuarios', 'resultado'])->findOrFail($id);
        $resultado = $partido->resultado;

        if (!$resultado) {
            return response()->json(['mensaje' => 'Todavía no hay resultado para confirmar'], 404);
        }

        if (!$this->ventanaResultadoAbierta($partido)) {
            return response()->json(['mensaje' => 'La ventana para confirmar el resultado ha terminado'], 403);
        }

        $participante = $this->participante($partido, $request->user()->id_usuario);

        if (!$participante || !$participante->pivot->es_capitan) {
            return response()->json(['mensaje' => 'Solo los capitanes pueden confirmar el resultado'], 403);
        }

        if ($participante->pivot->equipo_asignado === 'Equipo A') {
            $resultado->confirmado_local = true;
        }

        if ($participante->pivot->equipo_asignado === 'Equipo B') {
            $resultado->confirmado_visitante = true;
        }

        if ($resultado->confirmado_local && $resultado->confirmado_visitante) {
            $resultado->estado_resultado = 'cerrado';
            $this->cerrarResultado($partido, $resultado);
        } else {
            $resultado->estado_resultado = 'confirmado';
        }

        $resultado->save();

        return response()->json([
            'mensaje' => 'Resultado confirmado',
            'resultado' => $resultado
        ]);
    }

    private function resultadoConGoles(Partido $partido): array
    {
        return [
            'datos' => $partido->resultado,
            'goles' => $partido->goles,
            'goles_local' => $partido->goles()->where('equipo_sala', 'Equipo A')->count(),
            'goles_visitante' => $partido->goles()->where('equipo_sala', 'Equipo B')->count(),
        ];
    }

    private function comprobarCancelacionInterna(Partido $partido): bool
    {
        if ($partido->estado === 'cancelado' || !$partido->fecha || !$partido->hora) {
            return $partido->estado === 'cancelado';
        }

        if (now()->lt(Carbon::parse($partido->fecha . ' ' . $partido->hora))) {
            return false;
        }

        if ($this->jugadoresConfirmados($partido) >= $this->jugadoresMinimos($partido)) {
            return false;
        }

        $partido->estado = 'cancelado';
        $partido->save();

        return true;
    }

    private function jugadoresConfirmados(Partido $partido): int
    {
        return $partido->usuarios()->wherePivot('estado_participacion', 'confirmado')->count();
    }

    private function jugadoresMinimos(Partido $partido): int
    {
        $tipo = strtolower($partido->tipo_futbol ?? '');

        if (str_contains($tipo, '5v5') || str_contains($tipo, '5') || str_contains($tipo, 'sala')) {
            $jugadoresPorTipo = 10;
        } elseif (str_contains($tipo, '7')) {
            $jugadoresPorTipo = 16;
        } else {
            $jugadoresPorTipo = 22;
        }

        if ($partido->jugadores_minimos) {
            return max((int) $partido->jugadores_minimos, $jugadoresPorTipo);
        }

        return $jugadoresPorTipo;
    }

    private function fechaLimiteResultado(Partido $partido): ?Carbon
    {
        if (!$partido->fecha || !$partido->hora) {
            return null;
        }

        return Carbon::parse($partido->fecha . ' ' . $partido->hora)->addDay();
    }

    private function ventanaResultadoAbierta(Partido $partido): bool
    {
        if (!$partido->fecha || !$partido->hora || $partido->estado === 'cancelado') {
            return false;
        }

        $inicio = Carbon::parse($partido->fecha . ' ' . $partido->hora);
        $fin = $this->fechaLimiteResultado($partido);

        return now()->gte($inicio) && now()->lte($fin);
    }

    private function puedeRegistrarResultado(Request $request, Partido $partido): bool
    {
        $participante = $this->participante($partido, $request->user()->id_usuario);

        return $participante && (bool) $participante->pivot->es_capitan;
    }

    private function participante(Partido $partido, int $usuarioId)
    {
        return $partido->usuarios()
            ->where('usuarios.id_usuario', $usuarioId)
            ->wherePivot('estado_participacion', 'confirmado')
            ->first();
    }

    private function esCompetitivo(Partido $partido): bool
    {
        return (bool) $partido->es_competitivo || $partido->nivel === 'Competitivo';
    }

    private function cerrarResultado(Partido $partido, ResultadoPartido $resultado): void
    {
        if ($partido->estadisticas_actualizadas) {
            return;
        }

        DB::transaction(function () use ($partido, $resultado) {
            $participantes = $partido->usuarios()->wherePivot('estado_participacion', 'confirmado')->get();
            $golesPorUsuario = GolPartido::where('id_partido', $partido->id_partido)
                ->selectRaw('id_usuario, count(*) as total')
                ->groupBy('id_usuario')
                ->pluck('total', 'id_usuario');

            foreach ($participantes as $usuario) {
                $estadistica = Estadistica::firstOrCreate(['id_usuario' => $usuario->id_usuario]);
                $goles = (int) ($golesPorUsuario[$usuario->id_usuario] ?? 0);
                $gana = $this->usuarioGana($usuario->pivot->equipo_asignado, $resultado);
                $porteriaCero = $this->sumaPorteriaCero($usuario->pivot->equipo_asignado, $usuario->pivot->posicion_asignada, $resultado);

                $estadistica->partidos_jugados = (int) $estadistica->partidos_jugados + 1;
                $estadistica->partidos_ganados = (int) $estadistica->partidos_ganados + ($gana ? 1 : 0);
                $estadistica->partidos_perdidos = (int) $estadistica->partidos_perdidos + ($gana ? 0 : 1);
                $estadistica->goles = (int) $estadistica->goles + $goles;
                $estadistica->porterias_cero = (int) $estadistica->porterias_cero + ($porteriaCero ? 1 : 0);
                $estadistica->save();

                if ($this->esCompetitivo($partido)) {
                    $competitivo = Competitivo::firstOrCreate(['id_usuario' => $usuario->id_usuario], [
                        'rango' => 'Bronce 1',
                        'puntos_competitivos' => 0,
                        'precio_mensual' => 3.99,
                    ]);

                    $competitivo->partidos_competitivos_jugados = (int) $competitivo->partidos_competitivos_jugados + 1;
                    $competitivo->partidos_competitivos_ganados = (int) $competitivo->partidos_competitivos_ganados + ($gana ? 1 : 0);
                    $competitivo->partidos_competitivos_perdidos = (int) $competitivo->partidos_competitivos_perdidos + ($gana ? 0 : 1);
                    $competitivo->goles_competitivo = (int) $competitivo->goles_competitivo + $goles;
                    $competitivo->porterias_cero_competitivo = (int) $competitivo->porterias_cero_competitivo + ($porteriaCero ? 1 : 0);
                    $competitivo->puntos_competitivos = max(0, (int) $competitivo->puntos_competitivos + ($gana ? 30 : 10));
                    $competitivo->save();
                }
            }

            $partido->estado = 'finalizado';
            $partido->estadisticas_actualizadas = true;
            $partido->save();
        });
    }

    private function usuarioGana(?string $equipo, ResultadoPartido $resultado): bool
    {
        if ($resultado->goles_local === $resultado->goles_visitante) {
            return false;
        }

        return $equipo === 'Equipo A'
            ? $resultado->goles_local > $resultado->goles_visitante
            : $resultado->goles_visitante > $resultado->goles_local;
    }

    private function sumaPorteriaCero(?string $equipo, ?string $posicion, ResultadoPartido $resultado): bool
    {
        if (!$this->esPorteroODefensa($posicion)) {
            return false;
        }

        return $equipo === 'Equipo A'
            ? (int) $resultado->goles_visitante === 0
            : (int) $resultado->goles_local === 0;
    }

    private function esPorteroODefensa(?string $posicion): bool
    {
        return in_array(strtoupper((string) $posicion), ['POR', 'DFC', 'LI', 'LD'], true);
    }
}

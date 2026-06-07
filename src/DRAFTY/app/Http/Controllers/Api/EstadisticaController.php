<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estadistica;
use App\Models\VotoMvp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controlador que agrupa la logica de estadistica en la API.
 */
class EstadisticaController extends Controller
{
    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function misEstadisticas(Request $request)
    {
        $estadistica = Estadistica::firstOrCreate(
            ['id_usuario' => $request->user()->id_usuario],
            [
                'partidos_jugados' => 0,
                'partidos_ganados' => 0,
                'partidos_perdidos' => 0,
                'goles' => 0,
                'asistencias' => 0,
                'porterias_cero' => 0,
                'tarjetas_amarillas' => 0,
                'tarjetas_rojas' => 0
            ]
        );

        $estadistica->mvps = $this->contarMvpsUsuario($request->user()->id_usuario);
        $estadistica->torneos = $this->estadisticasTorneosUsuario($request->user()->id_usuario);

        return response()->json($estadistica);
    }

    /**
     * Devuelve el listado principal de recursos.
     */
    public function index()
    {
        return response()->json(Estadistica::all());
    }

    /**
     * Guarda un nuevo recurso con los datos recibidos.
     */
    public function store(Request $request)
    {
        $estadistica = Estadistica::create($request->all());
        return response()->json($estadistica, 201);
    }

    /**
     * Devuelve el detalle del recurso solicitado.
     */
    public function show($id)
    {
        return response()->json(Estadistica::findOrFail($id));
    }

    /**
     * Actualiza los datos del recurso indicado.
     */
    public function update(Request $request, $id)
    {
        $estadistica = Estadistica::findOrFail($id);
        $estadistica->update($request->all());

        return response()->json($estadistica);
    }

    /**
     * Elimina el recurso indicado cuando el usuario tiene permiso.
     */
    public function destroy($id)
    {
        Estadistica::findOrFail($id)->delete();
        return response()->json(['mensaje' => 'Estadistica eliminada']);
    }

    /**
     * Gestiona votos de MVP.
     */
    private function contarMvpsUsuario(int $idUsuario): int
    {
        $votosPorPartido = VotoMvp::query()
            ->join('partidos', 'partidos.id_partido', '=', 'votos_mvp.id_partido')
            ->whereNotNull('partidos.fecha')
            ->whereNotNull('partidos.hora')
            ->where(function ($query) {
                $query->whereRaw("LOWER(TRIM(COALESCE(partidos.estado, ''))) != ?", ['cancelado'])
                    ->orWhereNull('partidos.estado');
            })
            ->whereRaw('DATE_ADD(TIMESTAMP(partidos.fecha, partidos.hora), INTERVAL 24 HOUR) < ?', [now()])
            ->selectRaw('votos_mvp.id_partido, votos_mvp.id_usuario_votado, SUM(votos_mvp.peso_voto) as puntos')
            ->groupBy('votos_mvp.id_partido', 'votos_mvp.id_usuario_votado')
            ->get()
            ->groupBy('id_partido');

        return $votosPorPartido->filter(function ($rankingPartido) use ($idUsuario) {
            $maxPuntos = $rankingPartido->max('puntos');

            return $rankingPartido->contains(function ($fila) use ($idUsuario, $maxPuntos) {
                return (int) $fila->id_usuario_votado === (int) $idUsuario
                    && (int) $fila->puntos === (int) $maxPuntos;
            });
        })->count();
    }

    /**
     * Gestiona informacion relacionada con torneos.
     */
    private function estadisticasTorneosUsuario(int $idUsuario): array
    {
        $idsEquipos = DB::table('equipo_usuarios')
            ->where('id_usuario', $idUsuario)
            ->where(function ($query) {
                $query->where('estado', 'activo')->orWhereNull('estado');
            })
            ->where(function ($query) {
                $query->where('rol_en_equipo', '!=', 'invitado')->orWhereNull('rol_en_equipo');
            })
            ->pluck('id_equipo');

        $torneosJugados = DB::table('torneo_equipos')
            ->whereIn('id_equipo', $idsEquipos)
            ->where(function ($query) {
                $query->where('estado_inscripcion', 'aceptada')->orWhereNull('estado_inscripcion');
            })
            ->distinct('id_torneo')
            ->count('id_torneo');

        $torneosGanados = DB::table('torneo_partidos')
            ->where('ronda', 'Final')
            ->where('estado', 'jugado')
            ->whereIn('id_equipo_ganador', $idsEquipos)
            ->distinct('id_torneo')
            ->count('id_torneo');

        $estadisticasTorneo = DB::table('estadisticas_torneo_usuario')
            ->where('id_usuario', $idUsuario)
            ->selectRaw('COALESCE(SUM(goles), 0) as goles, COALESCE(SUM(porterias_cero), 0) as porterias_cero')
            ->first();

        return [
            'torneos_jugados' => $torneosJugados,
            'torneos_ganados' => $torneosGanados,
            'goles' => (int) ($estadisticasTorneo->goles ?? 0),
            'porterias_cero' => (int) ($estadisticasTorneo->porterias_cero ?? 0),
        ];
    }
}

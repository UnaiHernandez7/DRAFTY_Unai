<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Competitivo;
use App\Models\VotoMvp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CompetitivoController extends Controller
{
    public function miPerfil(Request $request)
    {
        $competitivo = $this->asegurarPerfilCompetitivo($request->user()->id_usuario);
        $competitivo->mvps_competitivo = $this->contarMvpsCompetitivosUsuario($request->user()->id_usuario);

        return response()->json($competitivo);
    }

    public function index()
    {
        return response()->json(Competitivo::all());
    }

    public function rankings(Request $request)
    {
        return response()->json($this->rankingsPorUsuarios(null, $request->user()->id_usuario));
    }

    public function rankingsAmigos(Request $request)
    {
        $usuarioId = $request->user()->id_usuario;
        $this->asegurarPerfilCompetitivo($usuarioId);

        $idsUsuarios = array_values(array_unique([...$this->idsAmigos($usuarioId), $usuarioId]));

        return response()->json($this->rankingsPorUsuarios($idsUsuarios, $usuarioId));
    }

    public function store(Request $request)
    {
        $competitivo = Competitivo::create($request->all());
        return response()->json($competitivo, 201);
    }

    public function show($id)
    {
        return response()->json(Competitivo::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $competitivo = Competitivo::findOrFail($id);
        $competitivo->update($request->all());

        return response()->json($competitivo);
    }

    public function destroy($id)
    {
        Competitivo::findOrFail($id)->delete();
        return response()->json(['mensaje' => 'Competitivo eliminado']);
    }

    private function rankingsPorUsuarios(?array $idsUsuarios = null, ?int $usuarioId = null): array
    {
        return [
            'goles' => $this->rankingPorCampo('goles_competitivo', $idsUsuarios, $usuarioId),
            'asistencias' => $this->rankingPorCampo('asistencias_competitivo', $idsUsuarios, $usuarioId),
            'porterias_cero' => $this->rankingPorCampo('porterias_cero_competitivo', $idsUsuarios, $usuarioId),
        ];
    }

    private function asegurarPerfilCompetitivo(int $usuarioId): Competitivo
    {
        return Competitivo::firstOrCreate(
            ['id_usuario' => $usuarioId],
            [
                'rango' => 'Bronce 1',
                'puntos_competitivos' => 0,
                'activo' => false,
                'precio_mensual' => 3.99,
                'estado_pago' => 'pendiente',
                'fecha_actualizacion' => now()
            ]
        );
    }

    private function rankingPorCampo(string $campo, ?array $idsUsuarios = null, ?int $usuarioId = null)
    {
        $consulta = Competitivo::with('usuario')
            ->whereNotNull('id_usuario')
            ->orderByDesc($campo)
            ->orderByDesc('puntos_competitivos')
            ->orderBy('id_usuario');

        if ($idsUsuarios !== null) {
            $consulta->whereIn('id_usuario', $idsUsuarios ?: [0]);
        }

        $rankingCompleto = $consulta->get()
            ->values()
            ->map(fn ($competitivo, $indice) => $this->formatearFilaRanking($competitivo, $campo, $indice + 1));

        $top = $rankingCompleto->take(5);

        if (!$usuarioId || $top->contains(fn ($item) => (int) $item['id_usuario'] === (int) $usuarioId)) {
            return $top->values();
        }

        $filaUsuario = $rankingCompleto->first(fn ($item) => (int) $item['id_usuario'] === (int) $usuarioId);

        return $filaUsuario
            ? $top->push($filaUsuario)->values()
            : $top->values();
    }

    private function formatearFilaRanking(Competitivo $competitivo, string $campo, int $posicion): array
    {
        return [
            'posicion' => $posicion,
            'id_usuario' => $competitivo->id_usuario,
            'nombre_usuario' => $competitivo->usuario?->nombre_usuario,
            'foto_perfil' => $competitivo->usuario?->foto_perfil,
            'rango' => $competitivo->rango,
            'puntos_competitivos' => $competitivo->puntos_competitivos,
            'valor' => $competitivo->{$campo} ?? 0,
        ];
    }

    private function idsAmigos(int $usuarioId): array
    {
        if (!Schema::hasTable('amistades')) {
            return [];
        }

        $columnaEmisor = Schema::hasColumn('amistades', 'id_usuario_emisor') ? 'id_usuario_emisor' : 'id_usuario';
        $columnaReceptor = Schema::hasColumn('amistades', 'id_usuario_receptor') ? 'id_usuario_receptor' : 'id_amigo';

        return DB::table('amistades')
            ->whereIn('estado', ['aceptada', 'aceptado'])
            ->where(function ($query) use ($usuarioId, $columnaEmisor, $columnaReceptor) {
                $query->where($columnaEmisor, $usuarioId)
                    ->orWhere($columnaReceptor, $usuarioId);
            })
            ->get()
            ->map(fn ($amistad) => (int) $amistad->{$columnaEmisor} === (int) $usuarioId
                ? (int) $amistad->{$columnaReceptor}
                : (int) $amistad->{$columnaEmisor}
            )
            ->values()
            ->all();
    }

    private function contarMvpsCompetitivosUsuario(int $idUsuario): int
    {
        $votosPorPartido = VotoMvp::query()
            ->join('partidos', 'partidos.id_partido', '=', 'votos_mvp.id_partido')
            ->whereNotNull('partidos.fecha')
            ->whereNotNull('partidos.hora')
            ->where(function ($query) {
                $query->whereRaw("LOWER(TRIM(COALESCE(partidos.estado, ''))) != ?", ['cancelado'])
                    ->orWhereNull('partidos.estado');
            })
            ->where(function ($query) {
                $query->where('partidos.es_competitivo', true)
                    ->orWhere('partidos.nivel', 'Competitivo');
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
}

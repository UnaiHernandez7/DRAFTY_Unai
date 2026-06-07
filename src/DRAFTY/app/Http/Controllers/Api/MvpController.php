<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Partido;
use App\Models\VotoMvp;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Controlador que agrupa la logica de mvp en la API.
 */
class MvpController extends Controller
{
    /**
     * Devuelve el listado principal de recursos.
     */
    public function index($id)
    {
        Partido::findOrFail($id);

        $votos = VotoMvp::with(['votado', 'votante'])
            ->where('id_partido', $id)
            ->get();

        $ranking = $votos
            ->groupBy('id_usuario_votado')
            ->map(function ($items) {
                $votado = $items->first()->votado;

                return [
                    'id_usuario' => $votado?->id_usuario,
                    'nombre_usuario' => $votado?->nombre_usuario,
                    'total' => $items->sum('peso_voto'),
                ];
            })
            ->sortByDesc('total')
            ->values();

        return response()->json([
            'ranking' => $ranking,
            'votos' => $votos
        ]);
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function votar(Request $request, $id)
    {
        $datos = $request->validate([
            'id_usuario_votado' => 'required|integer'
        ]);

        $partido = Partido::with('usuarios')->findOrFail($id);

        if (!$this->ventanaAbierta($partido)) {
            return response()->json(['mensaje' => 'La votacion MVP no esta abierta'], 403);
        }

        $votante = $this->participante($partido, $request->user()->id_usuario);
        $votado = $this->participante($partido, $datos['id_usuario_votado']);

        if (!$votante || !$votado) {
            return response()->json(['mensaje' => 'Solo pueden votar y recibir voto participantes del partido'], 403);
        }

        if ((int) $votante->id_usuario === (int) $votado->id_usuario) {
            return response()->json(['mensaje' => 'No puedes votarte a ti mismo'], 422);
        }

        $voto = VotoMvp::updateOrCreate(
            [
                'id_partido' => $partido->id_partido,
                'id_usuario_votante' => $votante->id_usuario
            ],
            [
                'id_usuario_votado' => $votado->id_usuario,
                'peso_voto' => $votante->pivot->es_capitan ? 2 : 1
            ]
        );

        return response()->json([
            'mensaje' => 'Voto MVP registrado',
            'voto' => $voto
        ], 201);
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    private function participante(Partido $partido, int $usuarioId)
    {
        return $partido->usuarios
            ->first(fn ($usuario) => (int) $usuario->id_usuario === (int) $usuarioId && $usuario->pivot?->estado_participacion === 'confirmado');
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    private function ventanaAbierta(Partido $partido): bool
    {
        if (!$partido->fecha || !$partido->hora || $partido->estado === 'cancelado') {
            return false;
        }

        $inicio = Carbon::parse($partido->fecha . ' ' . $partido->hora);

        return now()->betweenIncluded($inicio, $inicio->copy()->addDay());
    }
}

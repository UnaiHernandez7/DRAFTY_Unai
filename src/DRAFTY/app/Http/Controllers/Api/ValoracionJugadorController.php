<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Partido;
use App\Models\ValoracionJugador;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Controlador que agrupa la logica de valoracionjugador en la API.
 */
class ValoracionJugadorController extends Controller
{
    /**
     * Guarda un nuevo recurso con los datos recibidos.
     */
    public function store(Request $request, $id)
    {
        $datos = $request->validate([
            'id_usuario_valorado' => 'required|integer',
            'puntuacion' => 'required|integer|min:1|max:5',
            'comentario' => 'nullable|string|max:500'
        ]);

        $partido = Partido::with('usuarios')->findOrFail($id);

        if (!$this->ventanaAbierta($partido)) {
            return response()->json(['mensaje' => 'La ventana de valoraciones no esta abierta'], 403);
        }

        $valorador = $this->participante($partido, $request->user()->id_usuario);
        $valorado = $this->participante($partido, $datos['id_usuario_valorado']);

        if (!$valorador || !$valorador->pivot->es_capitan) {
            return response()->json(['mensaje' => 'Solo los capitanes pueden valorar jugadores'], 403);
        }

        if (!$valorado) {
            return response()->json(['mensaje' => 'Solo puedes valorar participantes del partido'], 422);
        }

        $valoracion = ValoracionJugador::updateOrCreate(
            [
                'id_partido' => $partido->id_partido,
                'id_usuario_valorado' => $valorado->id_usuario,
                'id_usuario_valorador' => $valorador->id_usuario
            ],
            [
                'puntuacion' => $datos['puntuacion'],
                'comentario' => $datos['comentario'] ?? null
            ]
        );

        return response()->json([
            'mensaje' => 'Valoracion guardada',
            'valoracion' => $valoracion
        ], 201);
    }

    /**
     * Gestiona informacion de usuarios.
     */
    public function usuario($id)
    {
        $resumen = ValoracionJugador::where('id_usuario_valorado', $id)
            ->selectRaw('avg(puntuacion) as media, count(*) as total')
            ->first();

        return response()->json([
            'media' => round((float) ($resumen->media ?? 0), 2),
            'total' => (int) ($resumen->total ?? 0),
            'valoraciones' => ValoracionJugador::with(['partido', 'valorador'])
                ->where('id_usuario_valorado', $id)
                ->latest()
                ->limit(20)
                ->get()
        ]);
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

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GolPartido;
use App\Models\Partido;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Controlador que agrupa la logica de golpartido en la API.
 */
class GolPartidoController extends Controller
{
    /**
     * Guarda un nuevo recurso con los datos recibidos.
     */
    public function store(Request $request, $id)
    {
        $datos = $request->validate([
            'id_usuario' => 'required|integer',
            'minuto' => 'nullable|integer|min:1|max:90'
        ], [
            'minuto.max' => 'El minuto del gol no puede ser mayor que 90.',
            'minuto.min' => 'El minuto del gol debe ser como minimo 1.'
        ]);

        $partido = Partido::with(['usuarios', 'resultado'])->findOrFail($id);

        if (!$this->ventanaResultadoAbierta($partido)) {
            return response()->json(['mensaje' => 'No puedes registrar goles fuera de la ventana de resultado'], 403);
        }

        if (!$this->puedeGestionarGoles($request, $partido)) {
            return response()->json(['mensaje' => 'No tienes permiso para registrar goles'], 403);
        }

        if ($this->resultadoBloqueado($partido)) {
            return response()->json(['mensaje' => 'No puedes modificar goles cuando el resultado ya está cerrado'], 403);
        }

        $goleador = $partido->usuarios()
            ->where('usuarios.id_usuario', $datos['id_usuario'])
            ->wherePivot('estado_participacion', 'confirmado')
            ->first();

        if (!$goleador) {
            return response()->json(['mensaje' => 'El goleador debe ser participante confirmado'], 422);
        }

        $gol = GolPartido::create([
            'id_partido' => $partido->id_partido,
            'id_usuario' => $goleador->id_usuario,
            'equipo_sala' => $goleador->pivot->equipo_asignado,
            'id_equipo' => $goleador->pivot->equipo_asignado === 'Equipo A' ? $partido->id_equipo_local : $partido->id_equipo_visitante,
            'minuto' => $datos['minuto'] ?? null
        ]);

        return response()->json([
            'mensaje' => 'Gol anadido correctamente',
            'gol' => $gol->load('usuario')
        ], 201);
    }

    /**
     * Elimina el recurso indicado cuando el usuario tiene permiso.
     */
    public function destroy(Request $request, $id)
    {
        $gol = GolPartido::with(['partido.usuarios', 'partido.resultado'])->findOrFail($id);

        if (!$this->ventanaResultadoAbierta($gol->partido)) {
            return response()->json(['mensaje' => 'No puedes eliminar goles fuera de la ventana de resultado'], 403);
        }

        if (!$this->puedeGestionarGoles($request, $gol->partido)) {
            return response()->json(['mensaje' => 'No tienes permiso para eliminar goles'], 403);
        }

        if ($this->resultadoBloqueado($gol->partido)) {
            return response()->json(['mensaje' => 'No puedes modificar goles cuando el resultado ya está cerrado'], 403);
        }

        $gol->delete();

        return response()->json(['mensaje' => 'Gol eliminado correctamente']);
    }

    /**
     * Gestiona goles registrados.
     */
    private function puedeGestionarGoles(Request $request, Partido $partido): bool
    {
        return $partido->usuarios()
            ->where('usuarios.id_usuario', $request->user()->id_usuario)
            ->wherePivot('estado_participacion', 'confirmado')
            ->wherePivot('es_capitan', true)
            ->exists();
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    private function resultadoBloqueado(Partido $partido): bool
    {
        return in_array($partido->resultado?->estado_resultado, ['cerrado', 'sin_resultado'], true);
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    private function ventanaResultadoAbierta(Partido $partido): bool
    {
        if (!$partido->fecha || !$partido->hora || $partido->estado === 'cancelado') {
            return false;
        }

        $inicio = Carbon::parse($partido->fecha . ' ' . $partido->hora);

        return now()->betweenIncluded($inicio, $inicio->copy()->addDay());
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Competitivo;
use App\Models\Pago;
use Illuminate\Http\Request;

/**
 * Controlador que agrupa la logica de pago en la API.
 */
class PagoController extends Controller
{
    /**
     * Devuelve el listado principal de recursos.
     */
    public function index(Request $request)
    {
        if ($request->user()->rol !== 'admin') {
            return response()->json(['mensaje' => 'Solo el admin puede ver todos los pagos'], 403);
        }

        return response()->json(Pago::with('usuario')->latest()->get());
    }

    /**
     * Gestiona informacion del modo competitivo.
     */
    public function activarCompetitivo(Request $request)
    {
        $competitivo = Competitivo::firstOrCreate(
            ['id_usuario' => $request->user()->id_usuario],
            [
                'rango' => 'Bronce 1',
                'puntos_competitivos' => 0,
                'activo' => false,
                'precio_mensual' => 3.99,
                'estado_pago' => 'pendiente',
                'fecha_actualizacion' => now()
            ]
        );

        if ($competitivo->activo) {
            return response()->json(['mensaje' => 'El modo competitivo ya esta activo', 'competitivo' => $competitivo]);
        }

        $pagoPendiente = Pago::where('id_usuario', $request->user()->id_usuario)
            ->where('tipo_pago', 'competitivo')
            ->where('estado_pago', 'pendiente')
            ->latest()
            ->first();

        if ($pagoPendiente) {
            return response()->json([
                'mensaje' => 'Ya tienes un pago competitivo pendiente de confirmar.',
                'pago' => $pagoPendiente,
                'competitivo' => $competitivo->fresh()
            ]);
        }

        /**
         * Pago simulado para proyecto educativo: queda pendiente hasta confirmarlo.
         */
        $pago = Pago::create([
            'id_usuario' => $request->user()->id_usuario,
            'tipo_pago' => 'competitivo',
            'importe' => $competitivo->precio_mensual ?? 3.99,
            'estado_pago' => 'pendiente',
            'fecha_pago' => null
        ]);

        $competitivo->update([
            'activo' => false,
            'estado_pago' => 'pendiente',
            'fecha_actualizacion' => now()
        ]);

        return response()->json([
            'mensaje' => 'Pago simulado creado. El competitivo se activara cuando el pago se confirme.',
            'pago' => $pago,
            'competitivo' => $competitivo->fresh()
        ], 201);
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function confirmar(Request $request, $id)
    {
        if ($request->user()->rol !== 'admin') {
            return response()->json(['mensaje' => 'Solo el admin puede confirmar pagos'], 403);
        }

        $pago = Pago::findOrFail($id);
        $pago->update([
            'estado_pago' => 'pagado',
            'fecha_pago' => now()
        ]);

        $competitivo = Competitivo::firstOrCreate(
            ['id_usuario' => $pago->id_usuario],
            ['rango' => 'Bronce 1', 'puntos_competitivos' => 0, 'precio_mensual' => 3.99]
        );

        $this->activarPerfilCompetitivo($competitivo);

        return response()->json(['mensaje' => 'Pago confirmado', 'pago' => $pago]);
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function cancelar(Request $request, $id)
    {
        if ($request->user()->rol !== 'admin') {
            return response()->json(['mensaje' => 'Solo el admin puede cancelar pagos'], 403);
        }

        $pago = Pago::findOrFail($id);
        $pago->update(['estado_pago' => 'cancelado']);

        $tieneOtroPagoConfirmado = Pago::where('id_usuario', $pago->id_usuario)
            ->where('tipo_pago', 'competitivo')
            ->where('estado_pago', 'pagado')
            ->exists();

        if (!$tieneOtroPagoConfirmado) {
            Competitivo::where('id_usuario', $pago->id_usuario)->update([
                'activo' => false,
                'estado_pago' => 'cancelado',
                'fecha_inicio_suscripcion' => null,
                'fecha_fin_suscripcion' => null,
                'fecha_actualizacion' => now()
            ]);
        }

        return response()->json(['mensaje' => 'Pago cancelado', 'pago' => $pago]);
    }

    /**
     * Devuelve el detalle del recurso solicitado.
     */
    private function activarPerfilCompetitivo(Competitivo $competitivo): void
    {
        $competitivo->update([
            'activo' => true,
            'estado_pago' => 'pagado',
            'fecha_inicio_suscripcion' => now()->toDateString(),
            'fecha_fin_suscripcion' => now()->addMonth()->toDateString(),
            'fecha_actualizacion' => now()
        ]);
    }
}

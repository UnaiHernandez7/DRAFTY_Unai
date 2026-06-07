<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migracion anonima que modifica la estructura de la base de datos.
 */
return new class extends Migration {
    /**
     * Aplica los cambios de esta migracion en la base de datos.
     */
    public function up(): void
    {
        if (!Schema::hasTable('competitivo') || !Schema::hasTable('pagos')) {
            return;
        }

        DB::table('pagos')
            ->where('tipo_pago', 'competitivo')
            ->where('estado_pago', 'pagado')
            ->update([
                'estado_pago' => 'pendiente',
                'fecha_pago' => null,
                'updated_at' => now()
            ]);

        DB::table('competitivo')
            ->where('activo', true)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('pagos')
                    ->whereColumn('pagos.id_usuario', 'competitivo.id_usuario')
                    ->where('pagos.tipo_pago', 'competitivo')
                    ->where('pagos.estado_pago', 'pagado');
            })
            ->update([
                'activo' => false,
                'estado_pago' => 'pendiente',
                'fecha_inicio_suscripcion' => null,
                'fecha_fin_suscripcion' => null,
                'fecha_actualizacion' => now()
            ]);
    }

    /**
     * Revierte los cambios de esta migracion en la base de datos.
     */
    public function down(): void
    {
        /**
         * Sin operaciones adicionales.
         */
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id('id_pago');
            $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario')->onDelete('cascade');
            $table->enum('tipo_pago', ['competitivo'])->default('competitivo');
            $table->decimal('importe', 8, 2);
            $table->enum('estado_pago', ['pendiente', 'pagado', 'cancelado'])->default('pendiente');
            $table->dateTime('fecha_pago')->nullable();
            $table->timestamps();
        });

        Schema::table('competitivo', function (Blueprint $table) {
            if (!Schema::hasColumn('competitivo', 'activo')) {
                $table->boolean('activo')->default(false);
            }
            if (!Schema::hasColumn('competitivo', 'precio_mensual')) {
                $table->decimal('precio_mensual', 8, 2)->default(3.99);
            }
            if (!Schema::hasColumn('competitivo', 'fecha_inicio_suscripcion')) {
                $table->date('fecha_inicio_suscripcion')->nullable();
            }
            if (!Schema::hasColumn('competitivo', 'fecha_fin_suscripcion')) {
                $table->date('fecha_fin_suscripcion')->nullable();
            }
        });
    }

    /**
     * Revierte los cambios de esta migracion en la base de datos.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};

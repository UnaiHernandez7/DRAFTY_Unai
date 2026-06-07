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
        Schema::table('participantes_partido', function (Blueprint $table) {
            $table->string('posicion_asignada')->nullable()->after('equipo_asignado');
        });
    }

    /**
     * Revierte los cambios de esta migracion en la base de datos.
     */
    public function down(): void
    {
        Schema::table('participantes_partido', function (Blueprint $table) {
            $table->dropColumn('posicion_asignada');
        });
    }
};

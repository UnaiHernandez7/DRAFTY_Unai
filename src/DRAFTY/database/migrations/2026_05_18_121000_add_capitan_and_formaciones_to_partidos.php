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
        Schema::table('partidos', function (Blueprint $table) {
            $table->string('formacion_local')->default('4-3-3')->after('id_equipo_visitante');
            $table->string('formacion_visitante')->default('4-3-3')->after('formacion_local');
        });

        Schema::table('participantes_partido', function (Blueprint $table) {
            $table->boolean('es_capitan')->default(false)->after('posicion_asignada');
        });
    }

    /**
     * Revierte los cambios de esta migracion en la base de datos.
     */
    public function down(): void
    {
        Schema::table('participantes_partido', function (Blueprint $table) {
            $table->dropColumn('es_capitan');
        });

        Schema::table('partidos', function (Blueprint $table) {
            $table->dropColumn(['formacion_local', 'formacion_visitante']);
        });
    }
};

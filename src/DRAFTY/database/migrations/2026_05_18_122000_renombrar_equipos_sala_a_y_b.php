<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migracion anonima que modifica la estructura de la base de datos.
 */
return new class extends Migration {
    /**
     * Aplica los cambios de esta migracion en la base de datos.
     */
    public function up(): void
    {
        DB::table('participantes_partido')
            ->where('equipo_asignado', 'Local')
            ->update(['equipo_asignado' => 'Equipo A']);

        DB::table('participantes_partido')
            ->where('equipo_asignado', 'Visitante')
            ->update(['equipo_asignado' => 'Equipo B']);
    }

    /**
     * Revierte los cambios de esta migracion en la base de datos.
     */
    public function down(): void
    {
        DB::table('participantes_partido')
            ->where('equipo_asignado', 'Equipo A')
            ->update(['equipo_asignado' => 'Local']);

        DB::table('participantes_partido')
            ->where('equipo_asignado', 'Equipo B')
            ->update(['equipo_asignado' => 'Visitante']);
    }
};

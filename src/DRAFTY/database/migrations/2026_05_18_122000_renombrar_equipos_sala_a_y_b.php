<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('participantes_partido')
            ->where('equipo_asignado', 'Local')
            ->update(['equipo_asignado' => 'Equipo A']);

        DB::table('participantes_partido')
            ->where('equipo_asignado', 'Visitante')
            ->update(['equipo_asignado' => 'Equipo B']);
    }

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

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('partidos', function (Blueprint $table) {
            $table->integer('goles_equipo_a')->nullable()->after('formacion_visitante');
            $table->integer('goles_equipo_b')->nullable()->after('goles_equipo_a');
            $table->text('goleadores')->nullable()->after('goles_equipo_b');
        });
    }

    public function down(): void
    {
        Schema::table('partidos', function (Blueprint $table) {
            $table->dropColumn(['goles_equipo_a', 'goles_equipo_b', 'goleadores']);
        });
    }
};

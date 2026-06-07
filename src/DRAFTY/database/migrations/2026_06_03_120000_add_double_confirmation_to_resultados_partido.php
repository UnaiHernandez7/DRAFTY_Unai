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
        Schema::table('resultados_partido', function (Blueprint $table) {
            if (!Schema::hasColumn('resultados_partido', 'goles_local_local')) {
                $table->integer('goles_local_local')->nullable()->after('goles_visitante');
            }

            if (!Schema::hasColumn('resultados_partido', 'goles_visitante_local')) {
                $table->integer('goles_visitante_local')->nullable()->after('goles_local_local');
            }

            if (!Schema::hasColumn('resultados_partido', 'goles_local_visitante')) {
                $table->integer('goles_local_visitante')->nullable()->after('goles_visitante_local');
            }

            if (!Schema::hasColumn('resultados_partido', 'goles_visitante_visitante')) {
                $table->integer('goles_visitante_visitante')->nullable()->after('goles_local_visitante');
            }
        });
    }

    /**
     * Revierte los cambios de esta migracion en la base de datos.
     */
    public function down(): void
    {
        Schema::table('resultados_partido', function (Blueprint $table) {
            foreach (['goles_local_local', 'goles_visitante_local', 'goles_local_visitante', 'goles_visitante_visitante'] as $columna) {
                if (Schema::hasColumn('resultados_partido', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });
    }
};

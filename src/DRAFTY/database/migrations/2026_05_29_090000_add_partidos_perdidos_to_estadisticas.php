<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migracion anonima que modifica la estructura de la base de datos.
 */
return new class extends Migration
{
    /**
     * Aplica los cambios de esta migracion en la base de datos.
     */
    public function up(): void
    {
        Schema::table('estadisticas', function (Blueprint $table) {
            if (!Schema::hasColumn('estadisticas', 'partidos_perdidos')) {
                $table->integer('partidos_perdidos')->default(0)->after('partidos_ganados');
            }
        });
    }

    /**
     * Revierte los cambios de esta migracion en la base de datos.
     */
    public function down(): void
    {
        Schema::table('estadisticas', function (Blueprint $table) {
            if (Schema::hasColumn('estadisticas', 'partidos_perdidos')) {
                $table->dropColumn('partidos_perdidos');
            }
        });
    }
};

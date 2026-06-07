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
        Schema::table('campos', function (Blueprint $table) {
            if (!Schema::hasColumn('campos', 'provincia')) {
                $table->string('provincia')->nullable()->after('ciudad');
            }

            if (!Schema::hasColumn('campos', 'codigo_postal')) {
                $table->string('codigo_postal')->nullable()->after('provincia');
            }

            if (!Schema::hasColumn('campos', 'latitud')) {
                $table->decimal('latitud', 10, 7)->nullable()->after('codigo_postal');
            }

            if (!Schema::hasColumn('campos', 'longitud')) {
                $table->decimal('longitud', 10, 7)->nullable()->after('latitud');
            }
        });

        Schema::table('partidos', function (Blueprint $table) {
            if (!Schema::hasColumn('partidos', 'descripcion')) {
                $table->text('descripcion')->nullable()->after('nivel');
            }

            if (!Schema::hasColumn('partidos', 'jugadores_minimos')) {
                $table->integer('jugadores_minimos')->nullable()->after('plazas_totales');
            }
        });
    }

    /**
     * Revierte los cambios de esta migracion en la base de datos.
     */
    public function down(): void
    {
        Schema::table('partidos', function (Blueprint $table) {
            if (Schema::hasColumn('partidos', 'descripcion')) {
                $table->dropColumn('descripcion');
            }

            if (Schema::hasColumn('partidos', 'jugadores_minimos')) {
                $table->dropColumn('jugadores_minimos');
            }
        });

        Schema::table('campos', function (Blueprint $table) {
            foreach (['longitud', 'latitud', 'codigo_postal', 'provincia'] as $columna) {
                if (Schema::hasColumn('campos', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });
    }
};

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
        if (Schema::hasTable('equipo_usuarios') && !Schema::hasColumn('equipo_usuarios', 'estado')) {
            Schema::table('equipo_usuarios', function (Blueprint $table) {
                $table->string('estado')->default('activo')->after('rol_en_equipo');
            });
        }

        if (!Schema::hasTable('estadisticas_equipo_usuario')) {
            Schema::create('estadisticas_equipo_usuario', function (Blueprint $table) {
                $table->id('id_estadistica_equipo_usuario');
                $table->foreignId('id_equipo')->constrained('equipos', 'id_equipo')->onDelete('cascade');
                $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario')->onDelete('cascade');
                $table->integer('partidos_jugados')->default(0);
                $table->integer('goles')->default(0);
                $table->integer('asistencias')->default(0);
                $table->integer('porterias_cero')->default(0);
                $table->timestamps();
                $table->unique(['id_equipo', 'id_usuario']);
            });
        }

        if (!Schema::hasTable('mensajes_equipo')) {
            Schema::create('mensajes_equipo', function (Blueprint $table) {
                $table->id('id_mensaje');
                $table->foreignId('id_equipo')->constrained('equipos', 'id_equipo')->onDelete('cascade');
                $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario')->onDelete('cascade');
                $table->text('mensaje');
                $table->timestamps();
            });
        }
    }

    /**
     * Revierte los cambios de esta migracion en la base de datos.
     */
    public function down(): void
    {
        Schema::dropIfExists('mensajes_equipo');
        Schema::dropIfExists('estadisticas_equipo_usuario');

        if (Schema::hasTable('equipo_usuarios') && Schema::hasColumn('equipo_usuarios', 'estado')) {
            Schema::table('equipo_usuarios', function (Blueprint $table) {
                $table->dropColumn('estado');
            });
        }
    }
};

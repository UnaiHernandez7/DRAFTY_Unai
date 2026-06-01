<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('torneos', function (Blueprint $table) {
            if (!Schema::hasColumn('torneos', 'id_organizador')) {
                $table->foreignId('id_organizador')->nullable()->after('id_torneo')->constrained('usuarios', 'id_usuario')->nullOnDelete();
            }
            if (!Schema::hasColumn('torneos', 'tipo_torneo')) {
                $table->string('tipo_torneo')->default('eliminatoria')->after('descripcion');
            }
            if (!Schema::hasColumn('torneos', 'tipo_futbol')) {
                $table->string('tipo_futbol')->default('7v7')->after('tipo_torneo');
            }
            if (!Schema::hasColumn('torneos', 'max_equipos')) {
                $table->integer('max_equipos')->default(8)->after('tipo_futbol');
            }
            if (!Schema::hasColumn('torneos', 'privacidad')) {
                $table->string('privacidad')->default('publico')->after('max_equipos');
            }
            if (!Schema::hasColumn('torneos', 'codigo_acceso')) {
                $table->string('codigo_acceso')->nullable()->after('privacidad');
            }
            if (!Schema::hasColumn('torneos', 'estado_torneo')) {
                $table->string('estado_torneo')->default('inscripcion_abierta')->after('codigo_acceso');
            }
        });

        Schema::table('torneo_equipos', function (Blueprint $table) {
            if (!Schema::hasColumn('torneo_equipos', 'estado_inscripcion')) {
                $table->string('estado_inscripcion')->default('aceptada');
            }
            if (!Schema::hasColumn('torneo_equipos', 'fecha_inscripcion')) {
                $table->dateTime('fecha_inscripcion')->nullable();
            }
            if (!Schema::hasColumn('torneo_equipos', 'posicion_final')) {
                $table->integer('posicion_final')->nullable();
            }
        });

        if (!Schema::hasTable('torneo_partidos')) {
            Schema::create('torneo_partidos', function (Blueprint $table) {
                $table->id('id_torneo_partido');
                $table->foreignId('id_torneo')->constrained('torneos', 'id_torneo')->onDelete('cascade');
                $table->string('ronda');
                $table->foreignId('id_equipo_local')->nullable()->constrained('equipos', 'id_equipo')->nullOnDelete();
                $table->foreignId('id_equipo_visitante')->nullable()->constrained('equipos', 'id_equipo')->nullOnDelete();
                $table->integer('goles_local')->nullable();
                $table->integer('goles_visitante')->nullable();
                $table->foreignId('id_equipo_ganador')->nullable()->constrained('equipos', 'id_equipo')->nullOnDelete();
                $table->string('estado')->default('pendiente');
                $table->dateTime('fecha_partido')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('estadisticas_torneo_usuario')) {
            Schema::create('estadisticas_torneo_usuario', function (Blueprint $table) {
                $table->id('id_estadistica_torneo_usuario');
                $table->foreignId('id_torneo')->constrained('torneos', 'id_torneo')->onDelete('cascade');
                $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario')->onDelete('cascade');
                $table->foreignId('id_equipo')->constrained('equipos', 'id_equipo')->onDelete('cascade');
                $table->integer('goles')->default(0);
                $table->integer('asistencias')->default(0);
                $table->integer('porterias_cero')->default(0);
                $table->integer('partidos_jugados')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('estadisticas_torneo_usuario');
        Schema::dropIfExists('torneo_partidos');
    }
};

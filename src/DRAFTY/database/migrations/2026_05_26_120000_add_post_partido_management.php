<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('partidos', function (Blueprint $table) {
            if (!Schema::hasColumn('partidos', 'jugadores_minimos')) {
                $table->integer('jugadores_minimos')->nullable()->after('plazas_totales');
            }

            if (!Schema::hasColumn('partidos', 'fecha_limite_resultado')) {
                $table->dateTime('fecha_limite_resultado')->nullable()->after('hora');
            }

            if (!Schema::hasColumn('partidos', 'id_arbitro')) {
                $table->foreignId('id_arbitro')->nullable()->after('id_creador')->constrained('usuarios', 'id_usuario')->nullOnDelete();
            }

            if (!Schema::hasColumn('partidos', 'es_competitivo')) {
                $table->boolean('es_competitivo')->default(false)->after('nivel');
            }

            if (!Schema::hasColumn('partidos', 'estadisticas_actualizadas')) {
                $table->boolean('estadisticas_actualizadas')->default(false)->after('goleadores');
            }
        });

        if (!Schema::hasTable('resultados_partido')) {
            Schema::create('resultados_partido', function (Blueprint $table) {
                $table->id('id_resultado');
                $table->foreignId('id_partido')->unique()->constrained('partidos', 'id_partido')->onDelete('cascade');
                $table->integer('goles_local')->default(0);
                $table->integer('goles_visitante')->default(0);
                $table->foreignId('registrado_por')->constrained('usuarios', 'id_usuario')->onDelete('cascade');
                $table->string('tipo_registro');
                $table->boolean('confirmado_local')->default(false);
                $table->boolean('confirmado_visitante')->default(false);
                $table->string('estado_resultado')->default('pendiente');
                $table->dateTime('fecha_limite_resultado')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('goles_partido')) {
            Schema::create('goles_partido', function (Blueprint $table) {
                $table->id('id_gol');
                $table->foreignId('id_partido')->constrained('partidos', 'id_partido')->onDelete('cascade');
                $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario')->onDelete('cascade');
                $table->foreignId('id_equipo')->nullable()->constrained('equipos', 'id_equipo')->nullOnDelete();
                $table->string('equipo_sala')->nullable();
                $table->integer('minuto')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('votos_mvp')) {
            Schema::create('votos_mvp', function (Blueprint $table) {
                $table->id('id_voto');
                $table->foreignId('id_partido')->constrained('partidos', 'id_partido')->onDelete('cascade');
                $table->foreignId('id_usuario_votante')->constrained('usuarios', 'id_usuario')->onDelete('cascade');
                $table->foreignId('id_usuario_votado')->constrained('usuarios', 'id_usuario')->onDelete('cascade');
                $table->integer('peso_voto')->default(1);
                $table->timestamps();
                $table->unique(['id_partido', 'id_usuario_votante']);
            });
        }

        if (!Schema::hasTable('valoraciones_jugador')) {
            Schema::create('valoraciones_jugador', function (Blueprint $table) {
                $table->id('id_valoracion');
                $table->foreignId('id_partido')->constrained('partidos', 'id_partido')->onDelete('cascade');
                $table->foreignId('id_usuario_valorado')->constrained('usuarios', 'id_usuario')->onDelete('cascade');
                $table->foreignId('id_usuario_valorador')->constrained('usuarios', 'id_usuario')->onDelete('cascade');
                $table->integer('puntuacion');
                $table->text('comentario')->nullable();
                $table->timestamps();
                $table->unique(['id_partido', 'id_usuario_valorado', 'id_usuario_valorador'], 'valoraciones_unicas_partido');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('valoraciones_jugador');
        Schema::dropIfExists('votos_mvp');
        Schema::dropIfExists('goles_partido');
        Schema::dropIfExists('resultados_partido');

        Schema::table('partidos', function (Blueprint $table) {
            foreach (['jugadores_minimos', 'fecha_limite_resultado', 'id_arbitro', 'es_competitivo', 'estadisticas_actualizadas'] as $columna) {
                if (Schema::hasColumn('partidos', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });
    }
};

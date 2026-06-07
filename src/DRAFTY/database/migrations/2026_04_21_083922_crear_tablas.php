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
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id('id_usuario');
            $table->string('nombre_usuario')->unique();
            $table->string('nombre');
            $table->string('apellido');
            $table->string('email')->unique();
            $table->string('contrasena');
            $table->date('fecha_registro')->nullable();
            $table->string('foto_perfil')->nullable();
            $table->string('ciudad')->nullable();
            $table->string('posiciones_favoritas')->nullable();
            $table->enum('rol', ['admin', 'usuario'])->default('usuario');
        });

        Schema::create('estadisticas', function (Blueprint $table) {
            $table->id('id_estadistica');
            $table->foreignId('id_usuario')->unique()->constrained('usuarios', 'id_usuario')->onDelete('cascade');
            $table->integer('partidos_jugados')->default(0);
            $table->integer('partidos_ganados')->default(0);
            $table->integer('goles')->default(0);
            $table->integer('asistencias')->default(0);
            $table->integer('porterias_cero')->default(0);
            $table->integer('tarjetas_amarillas')->default(0);
            $table->integer('tarjetas_rojas')->default(0);
        });

        Schema::create('campos', function (Blueprint $table) {
            $table->id('id_campo');
            $table->string('nombre_campo');
            $table->string('direccion');
            $table->string('ciudad');
            $table->string('tipo_campo');
            $table->decimal('precio_hora', 8, 2)->nullable();
        });

        Schema::create('equipos', function (Blueprint $table) {
            $table->id('id_equipo');
            $table->string('nombre_equipo');
            $table->text('descripcion')->nullable();
            $table->string('privacidad')->default('publico');
            $table->date('fecha_creacion')->nullable();
            $table->foreignId('id_creador')->constrained('usuarios', 'id_usuario')->onDelete('cascade');
        });

        Schema::create('torneos', function (Blueprint $table) {
            $table->id('id_torneo');
            $table->string('nombre_torneo');
            $table->text('descripcion')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->decimal('cuota_inscripcion', 8, 2)->nullable();
            $table->string('premio')->nullable();
            $table->string('estado')->nullable();
        });

        Schema::create('partidos', function (Blueprint $table) {
            $table->id('id_partido');
            $table->string('titulo');
            $table->date('fecha')->nullable();
            $table->time('hora')->nullable();
            $table->string('tipo_futbol')->nullable();
            $table->string('nivel')->nullable();
            $table->string('estado')->nullable();
            $table->integer('plazas_totales')->nullable();
            $table->foreignId('id_creador')->constrained('usuarios', 'id_usuario')->onDelete('cascade');
            $table->foreignId('id_campo')->constrained('campos', 'id_campo')->onDelete('cascade');
            $table->foreignId('id_equipo_local')->nullable()->constrained('equipos', 'id_equipo')->nullOnDelete();
            $table->foreignId('id_equipo_visitante')->nullable()->constrained('equipos', 'id_equipo')->nullOnDelete();
        });

        Schema::create('equipo_usuarios', function (Blueprint $table) {
            $table->foreignId('id_equipo')->constrained('equipos', 'id_equipo')->onDelete('cascade');
            $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario')->onDelete('cascade');
            $table->string('rol_en_equipo')->nullable();
            $table->primary(['id_equipo', 'id_usuario']);
        });

        Schema::create('torneo_equipos', function (Blueprint $table) {
            $table->foreignId('id_torneo')->constrained('torneos', 'id_torneo')->onDelete('cascade');
            $table->foreignId('id_equipo')->constrained('equipos', 'id_equipo')->onDelete('cascade');
            $table->primary(['id_torneo', 'id_equipo']);
        });

        Schema::create('participantes_partido', function (Blueprint $table) {
            $table->foreignId('id_partido')->constrained('partidos', 'id_partido')->onDelete('cascade');
            $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario')->onDelete('cascade');
            $table->string('estado_participacion')->nullable();
            $table->string('equipo_asignado')->nullable();
            $table->primary(['id_partido', 'id_usuario']);
        });

        Schema::create('competitivo', function (Blueprint $table) {
            $table->id('id_competitivo');
            $table->foreignId('id_usuario')->unique()->constrained('usuarios', 'id_usuario')->onDelete('cascade');
            $table->string('rango')->default('Bronce');
            $table->integer('puntos_competitivos')->default(0);
            $table->integer('partidos_competitivos_jugados')->default(0);
            $table->integer('partidos_competitivos_ganados')->default(0);
            $table->integer('partidos_competitivos_perdidos')->default(0);
            $table->integer('goles_competitivo')->default(0);
            $table->integer('asistencias_competitivo')->default(0);
            $table->integer('porterias_cero_competitivo')->default(0);
            $table->integer('tarjetas_amarillas_competitivo')->default(0);
            $table->integer('tarjetas_rojas_competitivo')->default(0);
            $table->integer('racha_actual')->default(0);
            $table->boolean('activo')->default(false);
            $table->decimal('precio_mensual', 8, 2)->default(3.99);
            $table->date('fecha_inicio_suscripcion')->nullable();
            $table->date('fecha_fin_suscripcion')->nullable();
            $table->enum('estado_pago', ['pendiente', 'pagado', 'cancelado'])->default('pendiente');
            $table->date('fecha_actualizacion')->nullable();
        });
    }

    /**
     * Revierte los cambios de esta migracion en la base de datos.
     */
    public function down(): void
    {
        Schema::dropIfExists('competitivo');
        Schema::dropIfExists('participantes_partido');
        Schema::dropIfExists('torneo_equipos');
        Schema::dropIfExists('equipo_usuarios');
        Schema::dropIfExists('partidos');
        Schema::dropIfExists('torneos');
        Schema::dropIfExists('equipos');
        Schema::dropIfExists('campos');
        Schema::dropIfExists('estadisticas');
        Schema::dropIfExists('usuarios');
    }
};

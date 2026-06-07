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
        Schema::table('partidos', function (Blueprint $table) {
            $table->boolean('es_publico')->default(true)->after('estado');
            $table->string('codigo_acceso')->nullable()->unique()->after('es_publico');
        });

        Schema::create('mensajes_partido', function (Blueprint $table) {
            $table->id('id_mensaje');
            $table->foreignId('id_partido')->constrained('partidos', 'id_partido')->onDelete('cascade');
            $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario')->onDelete('cascade');
            $table->text('mensaje');
            $table->dateTime('fecha_envio');
        });

        Schema::create('amistades', function (Blueprint $table) {
            $table->id('id_amistad');
            $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario')->onDelete('cascade');
            $table->foreignId('id_amigo')->constrained('usuarios', 'id_usuario')->onDelete('cascade');
            $table->string('estado')->default('aceptado');
            $table->dateTime('fecha_creacion');
            $table->unique(['id_usuario', 'id_amigo']);
        });
    }

    /**
     * Revierte los cambios de esta migracion en la base de datos.
     */
    public function down(): void
    {
        Schema::dropIfExists('amistades');
        Schema::dropIfExists('mensajes_partido');

        Schema::table('partidos', function (Blueprint $table) {
            $table->dropColumn(['es_publico', 'codigo_acceso']);
        });
    }
};

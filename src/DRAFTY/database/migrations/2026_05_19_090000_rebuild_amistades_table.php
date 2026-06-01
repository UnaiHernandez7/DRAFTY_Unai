<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('amistades');

        Schema::create('amistades', function (Blueprint $table) {
            $table->id('id_amistad');
            $table->foreignId('id_usuario_emisor')->constrained('usuarios', 'id_usuario')->onDelete('cascade');
            $table->foreignId('id_usuario_receptor')->constrained('usuarios', 'id_usuario')->onDelete('cascade');
            $table->enum('estado', ['pendiente', 'aceptada', 'rechazada'])->default('pendiente');
            $table->dateTime('fecha_solicitud');
            $table->dateTime('fecha_respuesta')->nullable();
            $table->unique(['id_usuario_emisor', 'id_usuario_receptor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amistades');
    }
};

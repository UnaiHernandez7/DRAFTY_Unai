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
        Schema::create('registros_pendientes', function (Blueprint $table) {
            $table->id('id_registro');
            $table->string('nombre_usuario', 60)->unique();
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('email')->unique();
            $table->string('contrasena');
            $table->string('ciudad', 100)->nullable();
            $table->string('posiciones_favoritas', 255)->nullable();
            $table->string('codigo_verificacion', 6);
            $table->timestamp('codigo_expira_en');
            $table->unsignedTinyInteger('intentos')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Revierte los cambios de esta migracion en la base de datos.
     */
    public function down(): void
    {
        Schema::dropIfExists('registros_pendientes');
    }
};

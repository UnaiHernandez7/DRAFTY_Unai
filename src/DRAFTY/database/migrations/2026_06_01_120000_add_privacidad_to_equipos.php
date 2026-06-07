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
        Schema::table('equipos', function (Blueprint $table) {
            if (!Schema::hasColumn('equipos', 'privacidad')) {
                $table->string('privacidad')->default('publico')->after('descripcion');
            }
        });
    }

    /**
     * Revierte los cambios de esta migracion en la base de datos.
     */
    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            if (Schema::hasColumn('equipos', 'privacidad')) {
                $table->dropColumn('privacidad');
            }
        });
    }
};

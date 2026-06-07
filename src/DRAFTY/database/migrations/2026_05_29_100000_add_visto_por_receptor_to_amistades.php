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
        Schema::table('amistades', function (Blueprint $table) {
            if (!Schema::hasColumn('amistades', 'visto_por_receptor')) {
                $table->boolean('visto_por_receptor')->default(false)->after('estado');
            }
        });
    }

    /**
     * Revierte los cambios de esta migracion en la base de datos.
     */
    public function down(): void
    {
        Schema::table('amistades', function (Blueprint $table) {
            if (Schema::hasColumn('amistades', 'visto_por_receptor')) {
                $table->dropColumn('visto_por_receptor');
            }
        });
    }
};

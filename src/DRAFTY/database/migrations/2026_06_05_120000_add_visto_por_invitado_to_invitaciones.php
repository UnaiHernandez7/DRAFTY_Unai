<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('participantes_partido') && !Schema::hasColumn('participantes_partido', 'visto_por_invitado')) {
            Schema::table('participantes_partido', function (Blueprint $table) {
                $table->boolean('visto_por_invitado')->default(false)->after('estado_participacion');
            });
        }

        if (Schema::hasTable('equipo_usuarios') && !Schema::hasColumn('equipo_usuarios', 'visto_por_invitado')) {
            Schema::table('equipo_usuarios', function (Blueprint $table) {
                $table->boolean('visto_por_invitado')->default(false)->after('estado');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('participantes_partido') && Schema::hasColumn('participantes_partido', 'visto_por_invitado')) {
            Schema::table('participantes_partido', function (Blueprint $table) {
                $table->dropColumn('visto_por_invitado');
            });
        }

        if (Schema::hasTable('equipo_usuarios') && Schema::hasColumn('equipo_usuarios', 'visto_por_invitado')) {
            Schema::table('equipo_usuarios', function (Blueprint $table) {
                $table->dropColumn('visto_por_invitado');
            });
        }
    }
};

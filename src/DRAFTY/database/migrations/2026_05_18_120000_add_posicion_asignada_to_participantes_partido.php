<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('participantes_partido', function (Blueprint $table) {
            $table->string('posicion_asignada')->nullable()->after('equipo_asignado');
        });
    }

    public function down(): void
    {
        Schema::table('participantes_partido', function (Blueprint $table) {
            $table->dropColumn('posicion_asignada');
        });
    }
};

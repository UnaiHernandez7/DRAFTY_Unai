<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estadisticas', function (Blueprint $table) {
            if (!Schema::hasColumn('estadisticas', 'partidos_perdidos')) {
                $table->integer('partidos_perdidos')->default(0)->after('partidos_ganados');
            }
        });
    }

    public function down(): void
    {
        Schema::table('estadisticas', function (Blueprint $table) {
            if (Schema::hasColumn('estadisticas', 'partidos_perdidos')) {
                $table->dropColumn('partidos_perdidos');
            }
        });
    }
};

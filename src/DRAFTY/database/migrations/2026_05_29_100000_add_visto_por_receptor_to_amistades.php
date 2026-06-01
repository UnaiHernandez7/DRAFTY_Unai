<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('amistades', function (Blueprint $table) {
            if (!Schema::hasColumn('amistades', 'visto_por_receptor')) {
                $table->boolean('visto_por_receptor')->default(false)->after('estado');
            }
        });
    }

    public function down(): void
    {
        Schema::table('amistades', function (Blueprint $table) {
            if (Schema::hasColumn('amistades', 'visto_por_receptor')) {
                $table->dropColumn('visto_por_receptor');
            }
        });
    }
};

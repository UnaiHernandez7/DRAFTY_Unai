<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('torneos', function (Blueprint $table) {
            if (!Schema::hasColumn('torneos', 'nombre_lugar')) {
                $table->string('nombre_lugar')->nullable()->after('premio');
            }
            if (!Schema::hasColumn('torneos', 'direccion')) {
                $table->string('direccion')->nullable()->after('nombre_lugar');
            }
            if (!Schema::hasColumn('torneos', 'ciudad')) {
                $table->string('ciudad')->nullable()->after('direccion');
            }
            if (!Schema::hasColumn('torneos', 'provincia')) {
                $table->string('provincia')->nullable()->after('ciudad');
            }
            if (!Schema::hasColumn('torneos', 'latitud')) {
                $table->decimal('latitud', 10, 7)->nullable()->after('provincia');
            }
            if (!Schema::hasColumn('torneos', 'longitud')) {
                $table->decimal('longitud', 10, 7)->nullable()->after('latitud');
            }
        });

        if (!Schema::hasTable('goles_torneo_partido')) {
            Schema::create('goles_torneo_partido', function (Blueprint $table) {
                $table->id('id_gol_torneo');
                $table->foreignId('id_torneo_partido')->constrained('torneo_partidos', 'id_torneo_partido')->onDelete('cascade');
                $table->foreignId('id_torneo')->constrained('torneos', 'id_torneo')->onDelete('cascade');
                $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario')->onDelete('cascade');
                $table->foreignId('id_equipo')->constrained('equipos', 'id_equipo')->onDelete('cascade');
                $table->integer('minuto')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('goles_torneo_partido');
    }
};

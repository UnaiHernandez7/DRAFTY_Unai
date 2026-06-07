<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Usuario;
use App\Models\Campo;
use App\Models\Equipo;
use App\Models\Partido;

/**
 * Seeder que carga datos de partido.
 */
class PartidoSeeder extends Seeder
{
    /**
     * Carga datos iniciales del proyecto en la base de datos.
     */
    public function run(): void
    {
        Partido::factory()->count(10)->create();
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UsuarioSeeder::class,
            CampoSeeder::class,
            CamposEspanaSeeder::class,
            EquipoSeeder::class,
            PartidoSeeder::class,
            PartidosEspanaSeeder::class,
        ]);
    }
}

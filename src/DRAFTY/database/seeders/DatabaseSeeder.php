<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder que carga datos de database.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Ejecuta los seeders principales de la aplicacion.
     *
     * @return void
     */
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

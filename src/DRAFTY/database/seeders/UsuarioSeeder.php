<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder que carga datos de usuario.
 */
class UsuarioSeeder extends Seeder
{
    /**
     * Crea o actualiza los usuarios base de la aplicacion.
     *
     * @return void
     */
    public function run(): void
    {
        Usuario::updateOrCreate(
            ['nombre_usuario' => 'admin'],
            [
                'nombre' => 'Admin',
                'apellido' => 'Drafty',
                'email' => 'admin@drafty.com',
                'contrasena' => Hash::make('admin123'),
                'fecha_registro' => now(),
                'rol' => 'admin'
            ]
        );

        Usuario::updateOrCreate(
            ['nombre_usuario' => 'unai'],
            [
                'nombre' => 'Unai',
                'apellido' => 'Usuario',
                'email' => 'unai@drafty.com',
                'contrasena' => Hash::make('123456'),
                'fecha_registro' => now(),
                'rol' => 'usuario'
            ]
        );
    }
}

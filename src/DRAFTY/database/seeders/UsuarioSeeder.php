<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        Usuario::create([
            'nombre_usuario' => 'admin',
            'nombre' => 'Admin',
            'apellido' => 'Drafty',
            'email' => 'admin@drafty.com',
            'contrasena' => Hash::make('admin123'),
            'fecha_registro' => now(),
            'rol' => 'admin'
        ]);

        Usuario::create([
            'nombre_usuario' => 'unai',
            'nombre' => 'Unai',
            'apellido' => 'Usuario',
            'email' => 'unai@drafty.com',
            'contrasena' => Hash::make('123456'),
            'fecha_registro' => now(),
            'rol' => 'usuario'
        ]);
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Equipo;
use App\Models\Usuario;

class EquipoSeeder extends Seeder
{
    public function run(): void
    {
        $usuario = Usuario::first();

        Equipo::create([
            'nombre_equipo' => 'Drafty FC',
            'descripcion' => 'Equipo principal de la comunidad DRAFTY',
            'fecha_creacion' => now(),
            'id_creador' => $usuario->id_usuario
        ]);

        Equipo::create([
            'nombre_equipo' => 'Los Galácticos',
            'descripcion' => 'Equipo amateur competitivo',
            'fecha_creacion' => now(),
            'id_creador' => $usuario->id_usuario
        ]);
    }
}
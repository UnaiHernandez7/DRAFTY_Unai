<?php

namespace Database\Seeders;

use App\Models\Equipo;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

/**
 * Seeder que carga datos de equipo.
 */
class EquipoSeeder extends Seeder
{
    /**
     * Crea o actualiza equipos demo asociados al primer usuario.
     *
     * @return void
     */
    public function run(): void
    {
        $usuario = Usuario::query()->first();

        if (!$usuario) {
            return;
        }

        Equipo::updateOrCreate(
            ['nombre_equipo' => 'Drafty FC'],
            [
                'descripcion' => 'Equipo principal de la comunidad DRAFTY',
                'fecha_creacion' => now(),
                'id_creador' => $usuario->id_usuario,
            ]
        );

        Equipo::updateOrCreate(
            ['nombre_equipo' => 'Los Galacticos'],
            [
                'descripcion' => 'Equipo amateur competitivo',
                'fecha_creacion' => now(),
                'id_creador' => $usuario->id_usuario,
            ]
        );
    }
}

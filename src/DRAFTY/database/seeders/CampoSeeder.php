<?php

namespace Database\Seeders;

use App\Models\Campo;
use Illuminate\Database\Seeder;

class CampoSeeder extends Seeder
{
    public function run(): void
    {
        Campo::updateOrCreate(
            ['nombre_campo' => 'Campo Municipal Alicante', 'ciudad' => 'Alicante'],
            [
                'direccion' => 'Av. Deportiva 12',
                'provincia' => 'Alicante',
                'latitud' => 38.3452,
                'longitud' => -0.4810,
                'tipo_campo' => 'Futbol 7',
                'precio_hora' => 35.00
            ]
        );

        Campo::updateOrCreate(
            ['nombre_campo' => 'Pabellon Centro', 'ciudad' => 'Elche'],
            [
                'direccion' => 'Calle Mayor 20',
                'provincia' => 'Alicante',
                'latitud' => 38.2699,
                'longitud' => -0.7126,
                'tipo_campo' => 'Futbol sala',
                'precio_hora' => 25.00
            ]
        );
    }
}

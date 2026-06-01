<?php

namespace Database\Seeders;

use App\Models\Campo;
use Illuminate\Database\Seeder;

class CampoSeeder extends Seeder
{
    public function run(): void
    {
        Campo::create([
            'nombre_campo' => 'Campo Municipal Alicante',
            'direccion' => 'Av. Deportiva 12',
            'ciudad' => 'Alicante',
            'provincia' => 'Alicante',
            'latitud' => 38.3452,
            'longitud' => -0.4810,
            'tipo_campo' => 'Futbol 7',
            'precio_hora' => 35.00
        ]);

        Campo::create([
            'nombre_campo' => 'Pabellon Centro',
            'direccion' => 'Calle Mayor 20',
            'ciudad' => 'Elche',
            'provincia' => 'Alicante',
            'latitud' => 38.2699,
            'longitud' => -0.7126,
            'tipo_campo' => 'Futbol sala',
            'precio_hora' => 25.00
        ]);
    }
}

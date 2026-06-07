<?php

namespace Database\Seeders;

use App\Models\Campo;
use Illuminate\Database\Seeder;

/**
 * Seeder que carga datos de camposespana.
 */
class CamposEspanaSeeder extends Seeder
{
    /**
     * Crea o actualiza campos demo repartidos por Espana.
     *
     * @return void
     */
    public function run(): void
    {
        foreach ($this->campos() as $campo) {
            Campo::updateOrCreate(
                [
                    'nombre_campo' => $campo['nombre_campo'],
                    'ciudad' => $campo['ciudad'],
                ],
                $campo
            );
        }
    }

    /**
     * Devuelve el catalogo fijo de campos espanoles.
     *
     * @return array<int, array<string, mixed>>
     */
    private function campos(): array
    {
        return [
            [
                'nombre_campo' => 'Centro Deportivo Canal',
                'direccion' => 'Av. de Filipinas, 54',
                'ciudad' => 'Madrid',
                'provincia' => 'Madrid',
                'codigo_postal' => '28003',
                'latitud' => 40.4408,
                'longitud' => -3.7079,
                'tipo_campo' => 'Futbol 7',
                'precio_hora' => 55,
            ],
            [
                'nombre_campo' => 'Campo Municipal La Elipa',
                'direccion' => 'C. de Alcalde Garrido Juaristi, 17',
                'ciudad' => 'Madrid',
                'provincia' => 'Madrid',
                'codigo_postal' => '28030',
                'latitud' => 40.4183,
                'longitud' => -3.6505,
                'tipo_campo' => 'Futbol 11',
                'precio_hora' => 70,
            ],
            [
                'nombre_campo' => 'CEM Mar Bella',
                'direccion' => 'Av. del Litoral, 86',
                'ciudad' => 'Barcelona',
                'provincia' => 'Barcelona',
                'codigo_postal' => '08005',
                'latitud' => 41.3945,
                'longitud' => 2.2091,
                'tipo_campo' => 'Futbol 7',
                'precio_hora' => 60,
            ],
            [
                'nombre_campo' => 'Polideportivo Virgen del Carmen Betero',
                'direccion' => 'C. de la Campillo de Altobuey, 1',
                'ciudad' => 'Valencia',
                'provincia' => 'Valencia',
                'codigo_postal' => '46022',
                'latitud' => 39.4709,
                'longitud' => -0.3297,
                'tipo_campo' => 'Futbol 7',
                'precio_hora' => 45,
            ],
            [
                'nombre_campo' => 'CDM San Pablo',
                'direccion' => 'Av. Dr. Laffon Soto, s/n',
                'ciudad' => 'Sevilla',
                'provincia' => 'Sevilla',
                'codigo_postal' => '41007',
                'latitud' => 37.3981,
                'longitud' => -5.9509,
                'tipo_campo' => 'Futbol sala',
                'precio_hora' => 35,
            ],
            [
                'nombre_campo' => 'Ciudad Deportiva de Malaga',
                'direccion' => 'C. La Era, s/n',
                'ciudad' => 'Malaga',
                'provincia' => 'Malaga',
                'codigo_postal' => '29016',
                'latitud' => 36.7201,
                'longitud' => -4.3942,
                'tipo_campo' => 'Futbol 11',
                'precio_hora' => 65,
            ],
            [
                'nombre_campo' => 'CDM Actur',
                'direccion' => 'C. Pablo Ruiz Picasso, 2',
                'ciudad' => 'Zaragoza',
                'provincia' => 'Zaragoza',
                'codigo_postal' => '50018',
                'latitud' => 41.6737,
                'longitud' => -0.8894,
                'tipo_campo' => 'Futbol sala',
                'precio_hora' => 30,
            ],
            [
                'nombre_campo' => 'Polideportivo Municipal Monte Tossal',
                'direccion' => 'C. Foguerer Jose Romeu Zarandieta',
                'ciudad' => 'Alicante',
                'provincia' => 'Alicante',
                'codigo_postal' => '03005',
                'latitud' => 38.3509,
                'longitud' => -0.4913,
                'tipo_campo' => 'Futbol sala',
                'precio_hora' => 30,
            ],
            [
                'nombre_campo' => 'Campo Municipal Jose Barnes',
                'direccion' => 'C. Mar Menor, 14',
                'ciudad' => 'Murcia',
                'provincia' => 'Murcia',
                'codigo_postal' => '30009',
                'latitud' => 37.9999,
                'longitud' => -1.1445,
                'tipo_campo' => 'Futbol 7',
                'precio_hora' => 40,
            ],
        ];
    }
}

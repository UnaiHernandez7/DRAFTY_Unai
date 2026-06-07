<?php

namespace Database\Factories;

use App\Models\Campo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory que genera datos de prueba de campo.
 */
class CampoFactory extends Factory
{
    /**
     * Modelo asociado a esta factory.
     */
    protected $model = Campo::class;

    /**
     * Define campos deportivos de ejemplo en ciudades espanolas.
     *
     * @return array<string, mixed> Atributos del campo generado.
     */
    public function definition(): array
    {
        $campos = [
            ['Madrid', 'Madrid', 40.4168, -3.7038],
            ['Barcelona', 'Barcelona', 41.3874, 2.1686],
            ['Valencia', 'Valencia', 39.4699, -0.3763],
            ['Sevilla', 'Sevilla', 37.3891, -5.9845],
            ['Malaga', 'Malaga', 36.7213, -4.4214],
            ['Zaragoza', 'Zaragoza', 41.6488, -0.8891],
            ['Alicante', 'Alicante', 38.3452, -0.4810],
            ['Murcia', 'Murcia', 37.9922, -1.1307],
        ];

        [$ciudad, $provincia, $latitud, $longitud] = fake()->randomElement($campos);
        $tipoCampo = fake()->randomElement(['Futbol sala', 'Futbol 7', 'Futbol 11']);

        return [
            'nombre_campo' => fake()->randomElement([
                'Ciudad Deportiva',
                'Campo Municipal',
                'Polideportivo Norte',
                'Centro Deportivo Sur',
                'Pabellon Municipal',
            ]) . ' ' . $ciudad,
            'direccion' => fake()->streetAddress(),
            'ciudad' => $ciudad,
            'provincia' => $provincia,
            'codigo_postal' => fake()->postcode(),
            'latitud' => $latitud + fake()->randomFloat(4, -0.035, 0.035),
            'longitud' => $longitud + fake()->randomFloat(4, -0.035, 0.035),
            'tipo_campo' => $tipoCampo,
            'precio_hora' => fake()->randomElement([25, 30, 35, 40, 45, 50, 60]),
        ];
    }
}

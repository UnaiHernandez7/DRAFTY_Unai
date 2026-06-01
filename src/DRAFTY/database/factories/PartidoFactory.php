<?php

namespace Database\Factories;

use App\Models\Partido;
use App\Models\Usuario;
use App\Models\Campo;
use App\Models\Equipo;
use Illuminate\Database\Eloquent\Factories\Factory;

class PartidoFactory extends Factory
{
    protected $model = Partido::class;

    public function definition(): array
    {
        return [
            'titulo' => fake()->randomElement([
                'Partido amistoso de fútbol 7',
                'Partido competitivo',
                'Fútbol sala entre semana',
                'Partido de entrenamiento',
                'Quedada futbolera'
            ]),

            'fecha' => fake()->dateTimeBetween('now', '+2 months')->format('Y-m-d'),
            'hora' => fake()->time('H:i:s'),

            'tipo_futbol' => fake()->randomElement([
                'Fútbol sala',
                'Fútbol 7',
                'Fútbol 11'
            ]),

            'nivel' => fake()->randomElement([
                'Principiante',
                'Intermedio',
                'Avanzado',
                'Competitivo'
            ]),

            'estado' => fake()->randomElement([
                'abierto',
                'completo',
                'cancelado'
            ]),

            'plazas_totales' => fake()->randomElement([14, 20, 26]),

            'id_creador' => Usuario::inRandomOrder()->first()?->id_usuario,
            'id_campo' => Campo::inRandomOrder()->first()?->id_campo,

            'id_equipo_local' => Equipo::inRandomOrder()->first()?->id_equipo,
            'id_equipo_visitante' => Equipo::inRandomOrder()->first()?->id_equipo,
        ];
    }
}

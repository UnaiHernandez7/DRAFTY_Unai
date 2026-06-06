<?php

namespace Database\Factories;

use App\Models\Campo;
use App\Models\Partido;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PartidoFactory extends Factory
{
    protected $model = Partido::class;

    public function definition(): array
    {
        $tipoFutbol = fake()->randomElement(['5v5', '7v7', '11v11']);
        $configuracion = $this->configuracionPorTipo($tipoFutbol);
        $ciudad = fake()->randomElement(['Madrid', 'Barcelona', 'Valencia', 'Sevilla', 'Malaga', 'Zaragoza', 'Alicante', 'Murcia']);

        return [
            'titulo' => fake()->randomElement([
                "Partido {$tipoFutbol} en {$ciudad}",
                "Quedada {$tipoFutbol} en {$ciudad}",
                "Pachanga {$tipoFutbol} nivel mixto",
                "Reto amistoso {$tipoFutbol}",
                "Entrenamiento abierto {$tipoFutbol}",
            ]),
            'fecha' => fake()->dateTimeBetween('+1 day', '+45 days')->format('Y-m-d'),
            'hora' => fake()->randomElement(['09:30', '10:00', '11:30', '18:00', '19:00', '20:30', '21:00']),
            'tipo_futbol' => $tipoFutbol,
            'nivel' => fake()->randomElement(['Casual', 'Intermedio', 'Alto']),
            'descripcion' => fake()->randomElement([
                'Partido abierto para completar equipos y jugar con buen ambiente.',
                'Trae botas, agua y ganas de competir sin malos rollos.',
                'Reserva pensada para jugadores de la zona.',
                'Partido organizado para probar DRAFTY con jugadores locales.',
            ]),
            'estado' => 'abierto',
            'jugadores_minimos' => $configuracion['jugadores_minimos'],
            'plazas_totales' => $configuracion['plazas_totales'],
            'es_competitivo' => false,
            'estadisticas_actualizadas' => false,
            'es_publico' => true,
            'codigo_acceso' => strtoupper(Str::random(6)),
            'id_creador' => Usuario::inRandomOrder()->first()?->id_usuario,
            'id_campo' => Campo::inRandomOrder()->first()?->id_campo,
            'id_equipo_local' => null,
            'id_equipo_visitante' => null,
        ];
    }

    public function futbolSala(): static
    {
        return $this->state(fn () => $this->datosPorTipo('5v5'));
    }

    public function futbol7(): static
    {
        return $this->state(fn () => $this->datosPorTipo('7v7'));
    }

    public function futbol11(): static
    {
        return $this->state(fn () => $this->datosPorTipo('11v11'));
    }

    public function enCampo(Campo $campo): static
    {
        return $this->state(fn () => [
            'id_campo' => $campo->id_campo,
            'titulo' => $this->tituloParaCampo($campo),
        ]);
    }

    private function datosPorTipo(string $tipoFutbol): array
    {
        $configuracion = $this->configuracionPorTipo($tipoFutbol);

        return [
            'tipo_futbol' => $tipoFutbol,
            'jugadores_minimos' => $configuracion['jugadores_minimos'],
            'plazas_totales' => $configuracion['plazas_totales'],
        ];
    }

    private function configuracionPorTipo(string $tipoFutbol): array
    {
        return match ($tipoFutbol) {
            '5v5' => ['jugadores_minimos' => 10, 'plazas_totales' => 14],
            '7v7' => ['jugadores_minimos' => 14, 'plazas_totales' => 20],
            default => ['jugadores_minimos' => 22, 'plazas_totales' => 26],
        };
    }

    private function tituloParaCampo(Campo $campo): string
    {
        return fake()->randomElement([
            "Partido en {$campo->ciudad}",
            "Quedada DRAFTY en {$campo->ciudad}",
            "Pachanga en {$campo->nombre_campo}",
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Campo;
use App\Models\Partido;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PartidosEspanaSeeder extends Seeder
{
    public function run(): void
    {
        $creador = Usuario::query()->orderBy('id_usuario')->first()
            ?? Usuario::create([
                'nombre_usuario' => 'drafty_seed',
                'nombre' => 'DRAFTY',
                'apellido' => 'Seeder',
                'email' => 'seed@draftyapp.es',
                'contrasena' => Hash::make('Drafty7711'),
                'fecha_registro' => now()->toDateString(),
                'ciudad' => 'Madrid',
                'posiciones_favoritas' => 'Mediocentro',
                'rol' => 'admin',
            ]);

        foreach ($this->partidos() as $partido) {
            $campo = Campo::query()
                ->where('nombre_campo', $partido['campo'])
                ->first();

            if (!$campo) {
                continue;
            }

            $fecha = Carbon::today()->addDays($partido['dias'])->toDateString();

            Partido::updateOrCreate(
                [
                    'titulo' => $partido['titulo'],
                    'fecha' => $fecha,
                    'id_campo' => $campo->id_campo,
                ],
                [
                    'hora' => $partido['hora'],
                    'tipo_futbol' => $partido['tipo_futbol'],
                    'nivel' => $partido['nivel'],
                    'descripcion' => $partido['descripcion'],
                    'estado' => 'abierto',
                    'jugadores_minimos' => $partido['jugadores_minimos'],
                    'plazas_totales' => $partido['plazas_totales'],
                    'es_competitivo' => false,
                    'estadisticas_actualizadas' => false,
                    'es_publico' => true,
                    'codigo_acceso' => strtoupper(Str::random(6)),
                    'id_creador' => $creador->id_usuario,
                    'id_campo' => $campo->id_campo,
                    'id_equipo_local' => null,
                    'id_equipo_visitante' => null,
                ]
            );
        }
    }

    private function partidos(): array
    {
        return [
            $this->partido('5v5', 'Futbol sala tarde en Alicante', 'Polideportivo Municipal Monte Tossal', 2, '19:00', 'Intermedio'),
            $this->partido('7v7', 'Pachanga 7v7 en Madrid Rio', 'Centro Deportivo Canal', 3, '20:30', 'Casual'),
            $this->partido('11v11', 'Partido grande domingo en Madrid', 'Campo Municipal La Elipa', 5, '11:30', 'Alto'),
            $this->partido('7v7', 'Quedada DRAFTY Barcelona', 'CEM Mar Bella', 6, '18:00', 'Intermedio'),
            $this->partido('7v7', 'Futbol 7 junto al Mediterraneo', 'Polideportivo Virgen del Carmen Betero', 8, '20:30', 'Casual'),
            $this->partido('5v5', 'Sala entre semana en Sevilla', 'CDM San Pablo', 9, '21:00', 'Casual'),
            $this->partido('11v11', 'Reto 11v11 en Malaga', 'Ciudad Deportiva de Malaga', 10, '10:00', 'Alto'),
            $this->partido('5v5', 'Futbol sala en Zaragoza', 'CDM Actur', 11, '19:00', 'Intermedio'),
            $this->partido('7v7', 'Tarde de futbol 7 en Murcia', 'Campo Municipal Jose Barnes', 12, '18:00', 'Casual'),
            $this->partido('5v5', 'Pachanga rapida Alicante', 'Polideportivo Municipal Monte Tossal', 14, '20:30', 'Casual'),
            $this->partido('7v7', 'Partido abierto Valencia', 'Polideportivo Virgen del Carmen Betero', 16, '19:00', 'Intermedio'),
            $this->partido('11v11', 'Domingo 11v11 Barcelona', 'CEM Mar Bella', 18, '11:30', 'Casual'),
        ];
    }

    private function partido(string $tipoFutbol, string $titulo, string $campo, int $dias, string $hora, string $nivel): array
    {
        $configuracion = match ($tipoFutbol) {
            '5v5' => ['jugadores_minimos' => 10, 'plazas_totales' => 14],
            '7v7' => ['jugadores_minimos' => 14, 'plazas_totales' => 20],
            default => ['jugadores_minimos' => 22, 'plazas_totales' => 26],
        };

        return [
            'tipo_futbol' => $tipoFutbol,
            'titulo' => $titulo,
            'campo' => $campo,
            'dias' => $dias,
            'hora' => $hora,
            'nivel' => $nivel,
            'descripcion' => "Partido {$tipoFutbol} abierto para jugadores de la zona. Reserva creada con datos demo de Espana.",
            ...$configuracion,
        ];
    }
}

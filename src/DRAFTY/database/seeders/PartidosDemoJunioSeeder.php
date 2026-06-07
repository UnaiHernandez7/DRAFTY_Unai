<?php

namespace Database\Seeders;

use App\Models\Campo;
use App\Models\Partido;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Seeder que carga datos de partidosdemojunio.
 */
class PartidosDemoJunioSeeder extends Seeder
{
    /**
     * Carga datos iniciales del proyecto en la base de datos.
     */
    public function run(): void
    {
        $creador = Usuario::query()->orderBy('id_usuario')->first()
            ?? Usuario::create([
                'nombre_usuario' => 'drafty_demo',
                'nombre' => 'DRAFTY',
                'apellido' => 'Demo',
                'email' => 'demo@draftyapp.es',
                'contrasena' => Hash::make('Drafty7711'),
                'fecha_registro' => now()->toDateString(),
                'ciudad' => 'Monovar',
                'posiciones_favoritas' => 'Mediocentro',
                'rol' => 'admin',
            ]);

        foreach ($this->partidos() as $datos) {
            $campo = Campo::updateOrCreate(
                [
                    'nombre_campo' => $datos['campo']['nombre_campo'],
                    'ciudad' => $datos['campo']['ciudad'],
                ],
                $this->soloColumnasExistentes('campos', $datos['campo'])
            );

            Partido::updateOrCreate(
                [
                    'titulo' => $datos['titulo'],
                    'fecha' => $datos['fecha'],
                    'id_campo' => $campo->id_campo,
                ],
                $this->soloColumnasExistentes('partidos', [
                    'hora' => $datos['hora'],
                    'tipo_futbol' => $datos['tipo_futbol'],
                    'nivel' => 'Casual',
                    'descripcion' => $datos['descripcion'],
                    'estado' => 'abierto',
                    'es_publico' => true,
                    'codigo_acceso' => strtoupper(Str::random(6)),
                    'plazas_totales' => $datos['plazas_totales'],
                    'jugadores_minimos' => $datos['jugadores_minimos'],
                    'es_competitivo' => false,
                    'id_creador' => $creador->id_usuario,
                    'id_campo' => $campo->id_campo,
                    'id_equipo_local' => null,
                    'id_equipo_visitante' => null,
                ])
            );
        }
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    private function soloColumnasExistentes(string $tabla, array $datos): array
    {
        return collect($datos)
            ->filter(fn ($valor, $columna) => Schema::hasColumn($tabla, $columna))
            ->all();
    }

    /**
     * Gestiona informacion relacionada con partidos.
     */
    private function partidos(): array
    {
        return [
            [
                'titulo' => 'Fútbol sala en Monóvar',
                'descripcion' => 'Partido de fútbol sala en el Pabellón Polideportivo Municipal de Monóvar.',
                'fecha' => '2026-06-16',
                'hora' => '19:00',
                'tipo_futbol' => '5v5',
                'jugadores_minimos' => 10,
                'plazas_totales' => 14,
                'campo' => [
                    'nombre_campo' => 'Pabellón Polideportivo Municipal de Monóvar',
                    'direccion' => 'Cno. Ravalet',
                    'ciudad' => 'Monovar',
                    'provincia' => 'Alicante',
                    'codigo_postal' => '03640',
                    'latitud' => 38.4386,
                    'longitud' => -0.8404,
                    'tipo_campo' => 'Futbol sala',
                    'precio_hora' => null,
                ],
            ],
            [
                'titulo' => 'Fútbol 11 en Monóvar',
                'descripcion' => 'Partido de fútbol 11 en el Campo de Fútbol Santa Bárbara de Monóvar.',
                'fecha' => '2026-06-16',
                'hora' => '20:30',
                'tipo_futbol' => '11v11',
                'jugadores_minimos' => 22,
                'plazas_totales' => 26,
                'campo' => [
                    'nombre_campo' => 'Campo de Fútbol Santa Bárbara',
                    'direccion' => 'Cno. Ravalet',
                    'ciudad' => 'Monovar',
                    'provincia' => 'Alicante',
                    'codigo_postal' => '03640',
                    'latitud' => 38.4390,
                    'longitud' => -0.8410,
                    'tipo_campo' => 'Futbol 11',
                    'precio_hora' => null,
                ],
            ],
            [
                'titulo' => 'Fútbol sala en Petrer',
                'descripcion' => 'Partido de fútbol sala en el Pabellón Municipal Gedeón e Isaías Guardiola de Petrer.',
                'fecha' => '2026-06-16',
                'hora' => '19:00',
                'tipo_futbol' => '5v5',
                'jugadores_minimos' => 10,
                'plazas_totales' => 14,
                'campo' => [
                    'nombre_campo' => 'Pabellón Municipal Gedeón e Isaías Guardiola',
                    'direccion' => 'Carrer Ortega y Gasset, 9',
                    'ciudad' => 'Petrer',
                    'provincia' => 'Alicante',
                    'codigo_postal' => '03610',
                    'latitud' => 38.4789,
                    'longitud' => -0.7906,
                    'tipo_campo' => 'Futbol sala',
                    'precio_hora' => null,
                ],
            ],
            [
                'titulo' => 'Fútbol 7 en Petrer',
                'descripcion' => 'Partido de fútbol 7 en el Campo Municipal El Barxell de Petrer.',
                'fecha' => '2026-06-16',
                'hora' => '20:30',
                'tipo_futbol' => '7v7',
                'jugadores_minimos' => 16,
                'plazas_totales' => 20,
                'campo' => [
                    'nombre_campo' => 'Campo Municipal El Barxell',
                    'direccion' => 'Ciudad Deportiva San Fernando',
                    'ciudad' => 'Petrer',
                    'provincia' => 'Alicante',
                    'codigo_postal' => '03610',
                    'latitud' => 38.4862,
                    'longitud' => -0.7822,
                    'tipo_campo' => 'Futbol 7',
                    'precio_hora' => null,
                ],
            ],
            [
                'titulo' => 'Fútbol sala en Elche',
                'descripcion' => 'Partido de fútbol sala en el Pabellón Esperanza Lag de Elche.',
                'fecha' => '2026-06-16',
                'hora' => '19:00',
                'tipo_futbol' => '5v5',
                'jugadores_minimos' => 10,
                'plazas_totales' => 14,
                'campo' => [
                    'nombre_campo' => 'Pabellón Esperanza Lag',
                    'direccion' => 'Ciudad Deportiva de Elche',
                    'ciudad' => 'Elche',
                    'provincia' => 'Alicante',
                    'codigo_postal' => '03290',
                    'latitud' => 38.2699,
                    'longitud' => -0.7126,
                    'tipo_campo' => 'Futbol sala',
                    'precio_hora' => null,
                ],
            ],
            [
                'titulo' => 'Fútbol 11 en Elche',
                'descripcion' => 'Partido de fútbol 11 en el Estadio Martínez Valero de Elche.',
                'fecha' => '2026-06-16',
                'hora' => '20:30',
                'tipo_futbol' => '11v11',
                'jugadores_minimos' => 22,
                'plazas_totales' => 26,
                'campo' => [
                    'nombre_campo' => 'Estadio Martínez Valero',
                    'direccion' => 'Avinguda de Manuel Martínez Valero, 3',
                    'ciudad' => 'Elche',
                    'provincia' => 'Alicante',
                    'codigo_postal' => '03208',
                    'latitud' => 38.2667,
                    'longitud' => -0.6633,
                    'tipo_campo' => 'Futbol 11',
                    'precio_hora' => null,
                ],
            ],
            [
                'titulo' => 'Fútbol sala en Alicante',
                'descripcion' => 'Partido de fútbol sala en el Pabellón Municipal Central Pitiu Rochel de Alicante.',
                'fecha' => '2026-06-16',
                'hora' => '19:00',
                'tipo_futbol' => '5v5',
                'jugadores_minimos' => 10,
                'plazas_totales' => 14,
                'campo' => [
                    'nombre_campo' => 'Pabellón Municipal Central Pitiu Rochel',
                    'direccion' => 'Calle Foguerer José Romeu Zarandieta',
                    'ciudad' => 'Alicante',
                    'provincia' => 'Alicante',
                    'codigo_postal' => '03005',
                    'latitud' => 38.3507,
                    'longitud' => -0.4890,
                    'tipo_campo' => 'Futbol sala',
                    'precio_hora' => null,
                ],
            ],
            [
                'titulo' => 'Fútbol 7 en Alicante',
                'descripcion' => 'Partido de fútbol 7 en el Campo de Fútbol Virgen del Remedio Luis Gómez de Alicante.',
                'fecha' => '2026-06-16',
                'hora' => '20:30',
                'tipo_futbol' => '7v7',
                'jugadores_minimos' => 16,
                'plazas_totales' => 20,
                'campo' => [
                    'nombre_campo' => 'Campo de Fútbol Virgen del Remedio Luis Gómez',
                    'direccion' => 'Virgen del Remedio',
                    'ciudad' => 'Alicante',
                    'provincia' => 'Alicante',
                    'codigo_postal' => '03011',
                    'latitud' => 38.3810,
                    'longitud' => -0.4938,
                    'tipo_campo' => 'Futbol 7',
                    'precio_hora' => null,
                ],
            ],
        ];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Amistad;
use App\Models\Campo;
use App\Models\Equipo;
use App\Models\MensajePartido;
use App\Models\Partido;
use App\Models\Usuario;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PartidoController extends Controller
{
    private array $posiciones = ['POR', 'LI', 'DFC', 'LD', 'MC', 'MCD', 'MCO', 'EI', 'DC', 'ED', 'ALA', 'PIV', 'SUP'];
    private array $formaciones = ['4-3-3', '4-3-1-2', '4-4-2', '3-5-2', '4-2-3-1', '3-3-1', '2-3-2', '3-2-2', '2-4-1', '2-1-3-1', '1-2-1', '2-1-1', '2-2', '1-1-2'];
    private array $equiposSala = ['Equipo A', 'Equipo B'];

    public function index()
    {
        return response()->json(
            Partido::query()
                ->with(['campo', 'usuarios'])
                ->withCount(['usuariosConfirmados as usuarios_count'])
                ->where('es_publico', true)
                ->where(function ($query) {
                    $query->whereRaw("LOWER(TRIM(COALESCE(estado, ''))) != ?", ['cancelado'])
                        ->orWhereNull('estado');
                })
                ->where(function ($query) {
                    $query->where('es_competitivo', false)
                        ->orWhereNull('es_competitivo');
                })
                ->where(function ($query) {
                    $query->whereRaw("LOWER(TRIM(COALESCE(nivel, ''))) != ?", ['competitivo'])
                        ->orWhereNull('nivel');
                })
                ->orderBy('fecha')
                ->orderBy('hora')
                ->limit(50)
                ->get()
                ->filter(fn ($partido) => $this->estaDentroDeVentanaActiva($partido))
                ->map(fn ($partido) => $this->decorarPartidoConPlazas($partido))
                ->values()
        );
    }

    public function cercanos(Request $request)
    {
        $usuario = $request->user('sanctum') ?? $request->user();
        $modo = $request->query('modo', 'cerca');
        $radio = $request->query('radio');
        $ciudad = $request->query('ciudad') ?: $usuario?->ciudad;
        $latitud = $request->query('latitud');
        $longitud = $request->query('longitud');
        $coordenadasUsuario = $this->resolverCoordenadasUsuario($latitud, $longitud, $ciudad, $modo);
        $tieneCoordenadas = $coordenadasUsuario !== null;

        $partidos = Partido::query()
            ->with(['campo', 'usuarios', 'equipoLocal', 'equipoVisitante', 'resultado'])
            ->withCount(['usuariosConfirmados as usuarios_count'])
            ->where('es_publico', true)
            ->where(function ($query) {
                $query->whereRaw("LOWER(TRIM(COALESCE(estado, ''))) != ?", ['cancelado'])
                    ->orWhereNull('estado');
            })
            ->where(function ($query) {
                $query->where('es_competitivo', false)
                    ->orWhereNull('es_competitivo');
            })
            ->where(function ($query) {
                $query->whereRaw("LOWER(TRIM(COALESCE(nivel, ''))) != ?", ['competitivo'])
                    ->orWhereNull('nivel');
            })
            ->get()
            ->filter(fn ($partido) => $this->estaDentroDeVentanaActiva($partido))
            ->map(function ($partido) use ($ciudad, $coordenadasUsuario, $modo, $tieneCoordenadas) {
                $partido = $this->decorarPartidoConPlazas($partido);
                $distancia = $tieneCoordenadas
                    ? $this->distanciaPartido($partido, $coordenadasUsuario['latitud'], $coordenadasUsuario['longitud'])
                    : 999999;

                $partido->prioridad_ciudad = $modo === 'todos' ? 0 : ($this->coincideCiudad($partido, $ciudad) ? 0 : 1);
                $partido->distancia_km = $distancia >= 999999 ? null : round($distancia, 1);
                $partido->distancia_orden = $modo === 'todos' ? 999999 : $distancia;

                return $partido;
            })
            ->filter(function ($partido) use ($modo, $radio, $ciudad, $tieneCoordenadas) {
                if (in_array($modo, ['cerca', 'desde-ciudad'], true)) {
                    if (!$tieneCoordenadas || $partido->distancia_km === null) {
                        return false;
                    }

                    return !$radio || (float) $partido->distancia_km <= (float) $radio;
                }

                if ($modo === 'mi-ciudad' && $ciudad) {
                    return $partido->prioridad_ciudad === 0;
                }

                return true;
            });

        $ordenados = $partidos->sortBy([
            ['distancia_orden', 'asc'],
            ['prioridad_ciudad', 'asc'],
            ['fecha', 'asc'],
            ['hora', 'asc'],
        ])->values()->map(function ($partido) {
            unset($partido->distancia_orden);

            return $partido;
        });

        return response()->json($ordenados);
    }

    public function adminIndex(Request $request)
    {
        if (!$this->esAdmin($request)) {
            return response()->json(['mensaje' => 'Solo el admin puede ver todos los partidos'], 403);
        }

        Partido::with('usuarios')->get()->each(fn ($partido) => $this->cancelarSiAlineacionesIncompletas($partido));

        return response()->json(Partido::withCount(['usuariosConfirmados as usuarios_count'])->get());
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'titulo' => 'required|string|max:120',
            'fecha' => 'required|date|after_or_equal:today',
            'hora' => 'required',
            'tipo_futbol' => 'required|in:5v5,7v7,11v11',
            'nivel' => 'required|in:Casual,Intermedio,Alto,Competitivo',
            'descripcion' => 'nullable|string|max:1000',
            'estado' => 'nullable|string',
            'es_publico' => 'nullable|boolean',
            'id_campo' => 'nullable|integer|exists:campos,id_campo',
            'id_equipo_local' => 'nullable|integer|exists:equipos,id_equipo',
            'id_equipo_visitante' => 'nullable|integer|exists:equipos,id_equipo',
            'ubicacion_modo' => 'nullable|in:existente,manual',
            'campo_nombre_campo' => 'nullable|string|max:120',
            'campo_direccion' => 'nullable|string|max:180',
            'campo_ciudad' => 'nullable|string|max:100',
            'campo_provincia' => 'nullable|string|max:100',
            'campo_codigo_postal' => 'nullable|string|max:20',
            'campo_latitud' => 'required|numeric|between:-90,90',
            'campo_longitud' => 'required|numeric|between:-180,180'
        ]);

        $inicio = Carbon::parse($datos['fecha'] . ' ' . $datos['hora']);

        if ($inicio->lt(now())) {
            return response()->json(['mensaje' => 'No puedes crear un partido en una fecha u hora pasada'], 422);
        }

        $datos['id_campo'] = $this->resolverCampoParaPartido($datos);
        $datos['id_creador'] = $request->user()->id_usuario;
        $datos['estado'] = $datos['estado'] ?? 'abierto';
        $datos['codigo_acceso'] = strtoupper(Str::random(6));
        $datos['jugadores_minimos'] = $this->jugadoresMinimos((object) $datos);
        $datos['plazas_totales'] = $this->capacidadTotal((object) $datos);
        $datos['es_competitivo'] = ($datos['nivel'] ?? '') === 'Competitivo';

        $partido = Partido::create(collect($datos)->only([
            'titulo',
            'fecha',
            'hora',
            'tipo_futbol',
            'nivel',
            'descripcion',
            'estado',
            'es_publico',
            'codigo_acceso',
            'plazas_totales',
            'jugadores_minimos',
            'es_competitivo',
            'id_creador',
            'id_campo',
            'id_equipo_local',
            'id_equipo_visitante'
        ])->all());
        $this->unirUsuarioAlPartido($partido, $request->user());
        $this->sincronizarEstadoPorPlazas($partido);

        return response()->json([
            'mensaje' => 'Partido creado correctamente',
            'partido' => $this->partidoConPlazas($partido)->load('campo'),
            'id_partido' => $partido->id_partido
        ], 201);
    }

    public function show($id)
    {
        $partido = Partido::query()
            ->withCount(['usuariosConfirmados as usuarios_count'])
            ->findOrFail($id);

        return response()->json($partido);
    }

    public function misPartidos(Request $request)
    {
        return response()->json(
            $this->partidosDelUsuario($request)
                ->filter(fn ($partido) => $this->estaDentroDeVentanaActiva($partido))
                ->sortBy([
                    ['fecha', 'asc'],
                    ['hora', 'asc'],
                ])
                ->values()
        );
    }

    public function misPartidosDetalle(Request $request)
    {
        return $this->misPartidos($request);
    }

    public function historialPartidos(Request $request)
    {
        return response()->json(
            $this->partidosDelUsuario($request, true)
                ->filter(fn ($partido) => $this->estaEnHistorial($partido))
                ->sortByDesc(fn ($partido) => trim(($partido->fecha ?? '') . ' ' . ($partido->hora ?? '')))
                ->values()
        );
    }

    public function unirsePorCodigo(Request $request, $codigo)
    {
        $partido = Partido::where('codigo_acceso', strtoupper($codigo))->firstOrFail();

        return $this->unirse($request, $partido->id_partido);
    }

    public function update(Request $request, $id)
    {
        if (!$this->esAdmin($request)) {
            return response()->json(['mensaje' => 'Solo el admin puede editar partidos'], 403);
        }

        $partido = Partido::findOrFail($id);
        $datos = $request->only([
            'titulo',
            'fecha',
            'hora',
            'tipo_futbol',
            'nivel',
            'estado',
            'plazas_totales',
            'jugadores_minimos',
            'id_arbitro',
            'es_competitivo',
            'id_campo',
            'goles_equipo_a',
            'goles_equipo_b',
            'goleadores'
        ]);

        $partido->update($datos);

        return response()->json($partido);
    }

    public function destroy($id)
    {
        Partido::findOrFail($id)->delete();
        return response()->json(['mensaje' => 'Partido eliminado']);
    }

    public function unirse(Request $request, $id)
    {
        $partido = Partido::findOrFail($id);
        $usuario = $request->user();

        if ($partido->estado === 'cancelado') {
            return response()->json([
                'mensaje' => 'Este partido esta cancelado'
            ], 400);
        }

        $participanteExistente = $partido->usuarios()
            ->where('usuarios.id_usuario', $usuario->id_usuario)
            ->first();

        if ($participanteExistente) {
            if ($participanteExistente->pivot->estado_participacion === 'pendiente') {
                $partido->usuarios()->updateExistingPivot($usuario->id_usuario, [
                    'estado_participacion' => 'confirmado'
                ]);
                $this->sincronizarEstadoPorPlazas($partido);

                return response()->json([
                    'mensaje' => 'Invitacion aceptada. Te has unido al partido correctamente',
                    'id_partido' => $partido->id_partido,
                    'partido' => $this->partidoConPlazas($partido)
                ]);
            }

            return response()->json([
                'mensaje' => 'Ya estás unido a este partido',
                'id_partido' => $partido->id_partido,
                'partido' => $this->partidoConPlazas($partido)
            ]);
        }

        if ($partido->usuarios()->wherePivot('estado_participacion', 'confirmado')->count() >= $this->capacidadTotal($partido)) {
            return response()->json([
                'mensaje' => 'El partido ya est? completo'
            ], 400);
        }

        $this->unirUsuarioAlPartido($partido, $usuario);
        $this->sincronizarEstadoPorPlazas($partido);

        return response()->json([
            'mensaje' => 'Te has unido al partido correctamente',
            'id_partido' => $partido->id_partido,
            'partido' => $this->partidoConPlazas($partido)
        ]);
    }

    public function invitarAmigo(Request $request, $id, $idUsuario)
    {
        $partido = Partido::findOrFail($id);
        $usuario = $request->user();
        $amigo = Usuario::findOrFail($idUsuario);

        if (!$this->sonAmigos($usuario->id_usuario, $amigo->id_usuario)) {
            return response()->json([
                'mensaje' => 'Solo puedes invitar a usuarios que ya son tus amigos'
            ], 403);
        }

        $estaEnPartido = $partido->usuarios()
            ->where('usuarios.id_usuario', $usuario->id_usuario)
            ->wherePivot('estado_participacion', 'confirmado')
            ->exists();

        if (!$estaEnPartido && !$this->esAdmin($request)) {
            return response()->json([
                'mensaje' => 'Tienes que estar en la sala para invitar a un amigo'
            ], 403);
        }

        if ($partido->estado === 'cancelado') {
            return response()->json([
                'mensaje' => 'No puedes invitar a una sala cancelada'
            ], 400);
        }

        $amigoYaEsta = $partido->usuarios()
            ->where('usuarios.id_usuario', $amigo->id_usuario)
            ->first();

        if ($amigoYaEsta) {
            return response()->json([
                'mensaje' => $amigoYaEsta->pivot->estado_participacion === 'pendiente'
                    ? 'Tu amigo ya tiene una invitacion pendiente para esta sala'
                    : 'Tu amigo ya est? en esta sala',
                'id_partido' => $partido->id_partido
            ]);
        }

        if ($partido->usuarios()->wherePivot('estado_participacion', 'confirmado')->count() >= $this->capacidadTotal($partido)) {
            return response()->json([
                'mensaje' => 'La sala ya est? completa'
            ], 400);
        }

        $this->invitarUsuarioAlPartido($partido, $amigo);

        return response()->json([
            'mensaje' => 'Invitacion a sala enviada correctamente',
            'id_partido' => $partido->id_partido
        ], 201);
    }

    public function candidatosPorPosicion(Request $request, $id)
    {
        $datos = $request->validate([
            'posicion' => 'required|string|in:Portero,Defensa,Mediocentro,Delantero'
        ]);

        $partido = Partido::findOrFail($id);

        if ($this->esPartidoCompetitivo($partido)) {
            return response()->json([
                'mensaje' => 'Las invitaciones por posición no están disponibles en competitivo'
            ], 403);
        }

        if (!$this->usuarioPuedeInvitarASala($request, $partido)) {
            return response()->json([
                'mensaje' => 'Tienes que estar en la sala para invitar jugadores'
            ], 403);
        }

        $idsOcupados = $partido->usuarios()
            ->pluck('usuarios.id_usuario')
            ->map(fn ($idUsuario) => (int) $idUsuario)
            ->push((int) $request->user()->id_usuario)
            ->values();
        $posicion = $datos['posicion'];
        $ciudadUsuario = $request->user()->ciudad;

        $usuarios = Usuario::query()
            ->select('id_usuario', 'nombre_usuario', 'nombre', 'apellido', 'foto_perfil', 'ciudad', 'posiciones_favoritas')
            ->whereNotIn('id_usuario', $idsOcupados)
            ->whereNotNull('posiciones_favoritas')
            ->where('posiciones_favoritas', 'like', "%{$posicion}%")
            ->when($ciudadUsuario, function ($query) use ($ciudadUsuario) {
                $query->orderByRaw('LOWER(COALESCE(ciudad, "")) = LOWER(?) DESC', [$ciudadUsuario]);
            })
            ->orderBy('nombre_usuario')
            ->limit(12)
            ->get();

        return response()->json($usuarios);
    }

    public function invitarPorPosicion(Request $request, $id, $idUsuario)
    {
        $datos = $request->validate([
            'posicion' => 'required|string|in:Portero,Defensa,Mediocentro,Delantero'
        ]);

        $partido = Partido::findOrFail($id);
        $usuario = $request->user();
        $invitado = Usuario::findOrFail($idUsuario);

        if ($this->esPartidoCompetitivo($partido)) {
            return response()->json([
                'mensaje' => 'Las invitaciones por posición no están disponibles en competitivo'
            ], 403);
        }

        if (!$this->usuarioPuedeInvitarASala($request, $partido)) {
            return response()->json([
                'mensaje' => 'Tienes que estar en la sala para invitar jugadores'
            ], 403);
        }

        if ((int) $usuario->id_usuario === (int) $invitado->id_usuario) {
            return response()->json([
                'mensaje' => 'No puedes invitarte a ti mismo'
            ], 422);
        }

        if (!$this->usuarioTienePosicionFavorita($invitado, $datos['posicion'])) {
            return response()->json([
                'mensaje' => 'Este jugador no tiene esa posición como favorita'
            ], 422);
        }

        if ($partido->estado === 'cancelado') {
            return response()->json([
                'mensaje' => 'No puedes invitar a una sala cancelada'
            ], 400);
        }

        $jugadorYaEsta = $partido->usuarios()
            ->where('usuarios.id_usuario', $invitado->id_usuario)
            ->first();

        if ($jugadorYaEsta) {
            return response()->json([
                'mensaje' => $jugadorYaEsta->pivot->estado_participacion === 'pendiente'
                    ? 'Este jugador ya tiene una invitación pendiente para esta sala'
                    : 'Este jugador ya está en esta sala',
                'id_partido' => $partido->id_partido
            ]);
        }

        if ($partido->usuarios()->wherePivot('estado_participacion', 'confirmado')->count() >= $this->capacidadTotal($partido)) {
            return response()->json([
                'mensaje' => 'La sala ya está completa'
            ], 400);
        }

        $this->invitarUsuarioAlPartido($partido, $invitado);

        return response()->json([
            'mensaje' => 'Invitación enviada correctamente',
            'id_partido' => $partido->id_partido
        ], 201);
    }

    public function invitaciones(Request $request)
    {
        return response()->json(
            $request->user()
                ->partidos()
                ->wherePivot('estado_participacion', 'pendiente')
                ->withCount(['usuariosConfirmados as usuarios_count'])
                ->orderBy('fecha')
                ->orderBy('hora')
                ->get()
        );
    }

    public function aceptarInvitacion(Request $request, $id)
    {
        $partido = Partido::findOrFail($id);
        $usuario = $request->user();

        $invitacion = $partido->usuarios()
            ->where('usuarios.id_usuario', $usuario->id_usuario)
            ->wherePivot('estado_participacion', 'pendiente')
            ->first();

        if (!$invitacion) {
            return response()->json(['mensaje' => 'No tienes una invitacion pendiente para esta sala'], 404);
        }

        if ($partido->usuarios()->wherePivot('estado_participacion', 'confirmado')->count() >= $this->capacidadTotal($partido)) {
            return response()->json(['mensaje' => 'La sala ya est? completa'], 400);
        }

        $partido->usuarios()->updateExistingPivot($usuario->id_usuario, [
            'estado_participacion' => 'confirmado'
        ]);
        $this->sincronizarEstadoPorPlazas($partido);

        return response()->json([
            'mensaje' => 'Invitacion aceptada',
            'id_partido' => $partido->id_partido,
            'partido' => $this->partidoConPlazas($partido)
        ]);
    }

    public function rechazarInvitacion(Request $request, $id)
    {
        $partido = Partido::findOrFail($id);
        $usuario = $request->user();

        $invitacion = $partido->usuarios()
            ->where('usuarios.id_usuario', $usuario->id_usuario)
            ->wherePivot('estado_participacion', 'pendiente')
            ->exists();

        if (!$invitacion) {
            return response()->json(['mensaje' => 'No tienes una invitacion pendiente para esta sala'], 404);
        }

        $partido->usuarios()->detach($usuario->id_usuario);

        return response()->json(['mensaje' => 'Invitacion rechazada']);
    }

    public function buscarPartidaCompetitiva(Request $request)
    {
        $datos = $request->validate([
            'modo' => 'nullable|in:solo,equipo',
            'tipo_futbol' => 'required|string',
            'fecha' => 'nullable|date',
            'proximidad' => 'nullable|boolean',
            'radio' => 'nullable|in:5,10,25,50',
            'id_equipo' => 'nullable|required_if:modo,equipo|integer'
        ]);

        $modo = $datos['modo'] ?? 'solo';
        $idEquipo = $modo === 'equipo' ? (int) $datos['id_equipo'] : null;
        $fecha = $datos['fecha'] ?? now()->toDateString();
        $hora = $this->horaCompetitivaCoherente($fecha);
        $usarProximidad = filter_var($datos['proximidad'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $radio = isset($datos['radio']) ? (float) $datos['radio'] : null;
        $perfilCompetitivo = $request->user()->competitivo;

        if (Carbon::parse($fecha . ' ' . $hora)->lt(now())) {
            return response()->json([
                'mensaje' => 'No puedes buscar o crear una partida competitiva en una hora pasada'
            ], 422);
        }

        if (!$perfilCompetitivo || !$perfilCompetitivo->activo) {
            return response()->json([
                'mensaje' => 'Activa el modo competitivo antes de buscar partida'
            ], 403);
        }

        $partidoCompetitivoActivo = $request->user()
            ->partidos()
            ->where('nivel', 'Competitivo')
            ->where('fecha', $fecha)
            ->where(function ($query) {
                $query->where('estado', '!=', 'cancelado')
                    ->orWhereNull('estado');
            })
            ->first();

        if ($partidoCompetitivoActivo) {
            return response()->json([
                'mensaje' => 'Ya estás en una partida competitiva para ese dia.',
                'id_partido' => $partidoCompetitivoActivo->id_partido
            ], 400);
        }

        if ($idEquipo) {
            $perteneceAlEquipo = Equipo::where('id_equipo', $idEquipo)
                ->whereHas('usuarios', function ($query) use ($request) {
                    $query->where('usuarios.id_usuario', $request->user()->id_usuario);
                })
                ->exists();

            if (!$perteneceAlEquipo) {
                return response()->json([
                    'mensaje' => 'No perteneces a ese equipo'
                ], 403);
            }
        }

        $partido = $this->encontrarPartidoCompetitivo($request, $datos['tipo_futbol'], $fecha, $idEquipo, $hora, $usarProximidad, $radio)
            ?? $this->crearPartidoCompetitivo($request, $datos['tipo_futbol'], $fecha, $idEquipo, $hora, $usarProximidad, $radio);

        if (!$partido) {
            return response()->json([
                'mensaje' => 'No hay campos disponibles para crear la partida competitiva'
            ], 422);
        }

        if ($idEquipo) {
            $this->asignarEquipoCompetitivo($partido, $idEquipo);
        }

        return $this->unirse($request, $partido->id_partido);
    }

    public function buscarCompetitivo(Request $request)
    {
        $datos = $request->validate([
            'modo_fecha' => 'nullable|in:hoy,manana,finde,fecha,aleatorio',
            'fecha' => 'nullable|date|after_or_equal:today',
            'proximidad' => 'nullable|boolean',
            'radio' => 'nullable|in:5,10,25,50',
            'tipo_futbol' => 'nullable|in:todos,5v5,7v7,11v11',
        ]);

        $usuario = $request->user();
        $modoFecha = $datos['modo_fecha'] ?? 'aleatorio';
        $usarProximidad = filter_var($datos['proximidad'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $radio = isset($datos['radio']) ? (float) $datos['radio'] : null;
        $tipoFutbol = $datos['tipo_futbol'] ?? 'todos';
        $coordenadasUsuario = null;

        if ($usarProximidad) {
            $coordenadasUsuario = $this->resolverCoordenadasUsuario(null, null, $usuario->ciudad, 'desde-ciudad');

            if (!$coordenadasUsuario) {
                return response()->json([
                    'mensaje' => 'Añade tu ubicación en el perfil para buscar por proximidad.'
                ], 422);
            }
        }

        $partidos = Partido::query()
            ->with(['campo', 'usuarios', 'equipoLocal', 'equipoVisitante', 'resultado'])
            ->withCount(['usuariosConfirmados as usuarios_count'])
            ->where(function ($query) {
                $query->where('es_competitivo', true)
                    ->orWhere('nivel', 'Competitivo');
            })
            ->where(function ($query) {
                $query->whereRaw("LOWER(TRIM(COALESCE(estado, ''))) NOT IN (?, ?)", ['cancelado', 'finalizado'])
                    ->orWhereNull('estado');
            })
            ->when($tipoFutbol !== 'todos', fn ($query) => $query->where('tipo_futbol', $tipoFutbol))
            ->whereIn(DB::raw("TIME_FORMAT(hora, '%H:%i')"), ['11:00', '20:00'])
            ->get()
            ->filter(fn ($partido) => $this->estaDentroDeVentanaActiva($partido))
            ->filter(fn ($partido) => $this->coincideFiltroFecha($partido, $modoFecha, $datos['fecha'] ?? null))
            ->map(function ($partido) use ($usarProximidad, $coordenadasUsuario, $usuario) {
                $partido = $this->decorarPartidoConPlazas($partido);
                $distancia = $usarProximidad && $coordenadasUsuario
                    ? $this->distanciaPartido($partido, $coordenadasUsuario['latitud'], $coordenadasUsuario['longitud'])
                    : 999999;

                $partido->distancia_km = $distancia >= 999999 ? null : round($distancia, 1);
                $partido->distancia_orden = $usarProximidad ? $distancia : 999999;
                $partido->prioridad_ciudad = $this->coincideCiudad($partido, $usuario->ciudad) ? 0 : 1;

                return $partido;
            })
            ->filter(function ($partido) use ($usarProximidad, $radio) {
                if (($partido->plazas_disponibles ?? 0) <= 0) {
                    return false;
                }

                if ($usarProximidad) {
                    if ($partido->distancia_km === null) {
                        return false;
                    }

                    return !$radio || (float) $partido->distancia_km <= $radio;
                }

                return true;
            })
            ->sortBy([
                ['distancia_orden', 'asc'],
                ['prioridad_ciudad', 'asc'],
                ['fecha', 'asc'],
                ['hora', 'asc'],
            ])
            ->values()
            ->map(function ($partido) {
                unset($partido->distancia_orden);
                return $partido;
            });

        return response()->json($partidos);
    }

    public function sala($id)
    {
        $partido = Partido::with([
            'campo',
            'usuarios.competitivo',
            'resultado.registrador',
            'goles.usuario',
            'votosMvp.votado',
            'valoraciones.valorador',
            'valoraciones.valorado'
        ])->findOrFail($id);
        $this->cancelarSiAlineacionesIncompletas($partido);
        $partido->refresh()->load([
            'campo',
            'usuarios.competitivo',
            'resultado.registrador',
            'goles.usuario',
            'votosMvp.votado',
            'valoraciones.valorador',
            'valoraciones.valorado'
        ]);
        $partido->setRelation(
            'usuarios',
            $partido->usuarios->filter(fn ($usuario) => $usuario->pivot?->estado_participacion === 'confirmado')->values()
        );
        $partido->jugadores_minimos = $partido->jugadores_minimos ?: $this->jugadoresMinimos($partido);
        $partido->fecha_limite_resultado = $this->fechaLimiteResultado($partido);
        $partido->ventana_resultado_abierta = $this->ventanaResultadoAbierta($partido);
        $partido->jugadores_confirmados = $partido->usuarios->count();
        $partido->faltan_jugadores_minimos = max(0, $partido->jugadores_minimos - $partido->jugadores_confirmados);

        return response()->json($partido);
    }

    public function mensajes($id)
    {
        Partido::findOrFail($id);

        return response()->json(
            MensajePartido::with('usuario')
                ->where('id_partido', $id)
                ->orderBy('fecha_envio')
                ->get()
        );
    }

    public function enviarMensaje(Request $request, $id)
    {
        $request->validate([
            'mensaje' => 'required|string|max:1000'
        ]);

        $partido = Partido::findOrFail($id);
        $estaUnido = $partido->usuarios()
            ->where('usuarios.id_usuario', $request->user()->id_usuario)
            ->wherePivot('estado_participacion', 'confirmado')
            ->exists();

        if (!$estaUnido && !$this->esAdmin($request)) {
            return response()->json(['mensaje' => 'Tienes que estar unido al partido'], 403);
        }

        $mensaje = MensajePartido::create([
            'id_partido' => $id,
            'id_usuario' => $request->user()->id_usuario,
            'mensaje' => $request->mensaje,
            'fecha_envio' => now()
        ]);

        return response()->json($mensaje->load('usuario'), 201);
    }

    public function cambiarPosicion(Request $request, $id)
    {
        $request->validate([
            'equipo_asignado' => 'required|string',
            'posicion_asignada' => 'required|string'
        ]);

        if (!in_array($request->equipo_asignado, $this->equiposSala)) {
            return response()->json([
                'mensaje' => 'Equipo no válido'
            ], 422);
        }

        $partido = Partido::findOrFail($id);

        if ($this->partidoHaEmpezado($partido)) {
            return response()->json([
                'mensaje' => 'El partido ya ha empezado y no se puede cambiar la alineacion'
            ], 422);
        }

        $usuario = $request->user();

        $participante = $partido->usuarios()
            ->where('usuarios.id_usuario', $usuario->id_usuario)
            ->wherePivot('estado_participacion', 'confirmado')
            ->first();

        if (!$participante) {
            return response()->json([
                'mensaje' => 'Primero tienes que unirte al partido'
            ], 403);
        }

        $equipoAnterior = $participante->pivot->equipo_asignado;
        $eraCapitan = $participante->pivot->es_capitan;
        $cambiaDeEquipo = $equipoAnterior !== $request->equipo_asignado;

        if ($cambiaDeEquipo && $partido->usuarios()->wherePivot('equipo_asignado', $request->equipo_asignado)->count() >= $this->capacidadPorEquipo($partido)) {
            return response()->json([
                'mensaje' => 'Ese equipo ya est? completo'
            ], 400);
        }

        $nuevoEquipoTieneCapitan = $partido->usuarios()
            ->wherePivot('equipo_asignado', $request->equipo_asignado)
            ->wherePivot('es_capitan', true)
            ->exists();

        $datosPivot = [
            'equipo_asignado' => $request->equipo_asignado,
            'posicion_asignada' => $request->posicion_asignada
        ];

        if ($cambiaDeEquipo) {
            $datosPivot['es_capitan'] = !$nuevoEquipoTieneCapitan;
        }

        $partido->usuarios()->updateExistingPivot($usuario->id_usuario, $datosPivot);

        if ($cambiaDeEquipo && $eraCapitan) {
            $this->pasarCapitan($partido, $equipoAnterior);
        }

        return response()->json([
            'mensaje' => 'Posicion actualizada correctamente'
        ]);
    }

    public function salir(Request $request, $id)
    {
        $partido = Partido::findOrFail($id);
        $usuario = $request->user();

        $participante = $partido->usuarios()
            ->where('usuarios.id_usuario', $usuario->id_usuario)
            ->first();

        if (!$participante) {
            return response()->json([
                'mensaje' => 'No estás unido a este partido'
            ], 400);
        }

        $equipo = $participante->pivot->equipo_asignado;
        $eraCapitan = $participante->pivot->es_capitan;

        $partido->usuarios()->detach($usuario->id_usuario);

        if ($partido->usuarios()->count() === 0) {
            $partido->delete();

            return response()->json([
                'mensaje' => 'Has salido del partido. La sala se ha eliminado porque estaba vacia',
                'partido_eliminado' => true
            ]);
        }

        if ($eraCapitan) {
            $this->pasarCapitan($partido, $equipo);
        }
        $this->sincronizarEstadoPorPlazas($partido);

        return response()->json([
            'mensaje' => 'Has salido del partido',
            'partido_eliminado' => false
        ]);
    }

    public function cambiarFormacion(Request $request, $id)
    {
        $request->validate([
            'equipo_asignado' => 'required|string',
            'formacion' => 'required|string'
        ]);

        if (!in_array($request->equipo_asignado, $this->equiposSala)) {
            return response()->json([
                'mensaje' => 'Equipo no válido'
            ], 422);
        }

        if (!in_array($request->formacion, $this->formaciones)) {
            return response()->json([
                'mensaje' => 'Formacion no valida'
            ], 422);
        }

        $partido = Partido::findOrFail($id);

        if ($this->partidoHaEmpezado($partido)) {
            return response()->json([
                'mensaje' => 'El partido ya ha empezado y no se puede cambiar la alineacion'
            ], 422);
        }

        if (!in_array($request->formacion, $this->formacionesPorTipo($partido))) {
            return response()->json([
                'mensaje' => 'Esa formacion no vale para este tipo de partido'
            ], 422);
        }

        $usuario = $request->user();

        $participante = $partido->usuarios()
            ->where('usuarios.id_usuario', $usuario->id_usuario)
            ->first();

        if (!$participante || !$participante->pivot->es_capitan) {
            return response()->json([
                'mensaje' => 'Solo el capitan puede cambiar la formacion'
            ], 403);
        }

        if ($participante->pivot->equipo_asignado !== $request->equipo_asignado) {
            return response()->json([
                'mensaje' => 'Solo puedes cambiar la formacion de tu equipo'
            ], 403);
        }

        if ($request->equipo_asignado === 'Equipo A') {
            $partido->formacion_local = $request->formacion;
        } else {
            $partido->formacion_visitante = $request->formacion;
        }

        $partido->save();

        return response()->json([
            'mensaje' => 'Formacion actualizada correctamente'
        ]);
    }

    public function cambiarResultado(Request $request, $id)
    {
        $request->validate([
            'goles_equipo_a' => 'nullable|integer|min:0',
            'goles_equipo_b' => 'nullable|integer|min:0',
            'goleadores' => 'nullable|string'
        ]);

        $partido = Partido::findOrFail($id);
        $this->cancelarSiAlineacionesIncompletas($partido);

        if (!$this->puedeEditarResultado($request, $partido)) {
            return response()->json([
                'mensaje' => 'Solo el admin o un capitan el día del partido puede editar el resultado'
            ], 403);
        }

        $partido->goles_equipo_a = $request->goles_equipo_a;
        $partido->goles_equipo_b = $request->goles_equipo_b;
        $partido->goleadores = $request->goleadores;
        $partido->save();

        return response()->json([
            'mensaje' => 'Resultado actualizado correctamente',
            'partido' => $partido
        ]);
    }

    public function cancelar(Request $request, $id)
    {
        if (!$this->esAdmin($request)) {
            return response()->json(['mensaje' => 'Solo el admin puede cancelar partidos'], 403);
        }

        $partido = Partido::findOrFail($id);
        $partido->estado = 'cancelado';
        $partido->save();

        return response()->json([
            'mensaje' => 'Partido cancelado correctamente',
            'partido' => $partido
        ]);
    }

    private function elegirEquipo(Partido $partido): string
    {
        $equipoA = $partido->usuarios()->wherePivot('equipo_asignado', 'Equipo A')->wherePivot('estado_participacion', 'confirmado')->count();
        $equipoB = $partido->usuarios()->wherePivot('equipo_asignado', 'Equipo B')->wherePivot('estado_participacion', 'confirmado')->count();

        return $equipoA <= $equipoB ? 'Equipo A' : 'Equipo B';
    }

    private function unirUsuarioAlPartido(Partido $partido, Usuario $usuario): void
    {
        $equipoAsignado = $this->elegirEquipo($partido);
        $esCapitan = $partido->usuarios()->wherePivot('equipo_asignado', $equipoAsignado)->count() === 0;

        $partido->usuarios()->attach($usuario->id_usuario, [
            'estado_participacion' => 'confirmado',
            'equipo_asignado' => $equipoAsignado,
            'posicion_asignada' => $this->elegirPosicion($usuario->posiciones_favoritas, $partido, $equipoAsignado),
            'es_capitan' => $esCapitan
        ]);
    }

    private function partidoConPlazas(Partido $partido): Partido
    {
        return $this->decorarPartidoConPlazas(
            $partido->fresh()->loadCount(['usuariosConfirmados as usuarios_count'])
        );
    }

    private function partidosDelUsuario(Request $request, bool $conHistorial = false)
    {
        $ids = DB::table('participantes_partido')
            ->where('id_usuario', $request->user()->id_usuario)
            ->where('estado_participacion', 'confirmado')
            ->pluck('id_partido');

        $relaciones = ['campo', 'equipoLocal', 'equipoVisitante', 'resultado'];

        if ($conHistorial) {
            $relaciones = array_merge($relaciones, [
                'goles.usuario',
                'votosMvp.votado',
            ]);
        }

        return Partido::query()
            ->whereIn('id_partido', $ids)
            ->where(function ($query) {
                $query->whereRaw("LOWER(TRIM(COALESCE(estado, ''))) != ?", ['cancelado'])
                    ->orWhereNull('estado');
            })
            ->with($relaciones)
            ->withCount(['usuariosConfirmados as usuarios_count'])
            ->get()
            ->map(fn ($partido) => $this->decorarPartidoConPlazas($partido));
    }

    private function resolverCampoParaPartido(array $datos): int
    {
        $modo = $datos['ubicacion_modo'] ?? (!empty($datos['id_campo']) ? 'existente' : 'manual');

        if ($modo === 'existente') {
            if (empty($datos['id_campo'])) {
                abort(response()->json(['mensaje' => 'Selecciona un campo existente'], 422));
            }

            $campo = Campo::findOrFail($datos['id_campo']);
            $campo->update([
                'latitud' => $datos['campo_latitud'],
                'longitud' => $datos['campo_longitud'],
            ]);

            return (int) $campo->id_campo;
        }

        foreach (['campo_nombre_campo', 'campo_direccion', 'campo_ciudad', 'campo_provincia'] as $campo) {
            if (empty($datos[$campo])) {
                abort(response()->json(['mensaje' => 'Completa la ubicación manual del partido'], 422));
            }
        }

        $campo = Campo::create([
            'nombre_campo' => $datos['campo_nombre_campo'],
            'direccion' => $datos['campo_direccion'],
            'ciudad' => $datos['campo_ciudad'],
            'provincia' => $datos['campo_provincia'],
            'codigo_postal' => $datos['campo_codigo_postal'] ?? null,
            'latitud' => $datos['campo_latitud'] ?? null,
            'longitud' => $datos['campo_longitud'] ?? null,
            'tipo_campo' => $datos['tipo_futbol'] ?? 'Futbol',
            'precio_hora' => null
        ]);

        return $campo->id_campo;
    }

    private function decorarPartidoConPlazas(Partido $partido): Partido
    {
        $capacidad = $this->capacidadTotal($partido);
        $ocupadas = (int) ($partido->usuarios_count ?? 0);

        $partido->plazas_totales_calculadas = $capacidad;
        $partido->plazas_disponibles = max(0, $capacidad - $ocupadas);
        $partido->estado_calculado = $ocupadas >= $capacidad ? 'completo' : 'abierto';

        if (!in_array($partido->estado, ['cancelado', 'finalizado'], true)) {
            $partido->estado = $partido->estado_calculado;
        }

        return $partido;
    }

    private function coincideCiudad(Partido $partido, ?string $ciudad): bool
    {
        if (!$ciudad || !$partido->campo?->ciudad) {
            return false;
        }

        return $this->normalizarUbicacion($partido->campo->ciudad) === $this->normalizarUbicacion($ciudad);
    }

    private function distanciaPartido(Partido $partido, $latitud, $longitud): float
    {
        $coordenadasPartido = $this->resolverCoordenadasCampo($partido);

        if (!$latitud || !$longitud || !$coordenadasPartido) {
            return 999999;
        }

        $lat1 = deg2rad((float) $latitud);
        $lon1 = deg2rad((float) $longitud);
        $lat2 = deg2rad((float) $coordenadasPartido['latitud']);
        $lon2 = deg2rad((float) $coordenadasPartido['longitud']);

        $dLat = $lat2 - $lat1;
        $dLon = $lon2 - $lon1;
        $a = sin($dLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dLon / 2) ** 2;

        return 6371 * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    private function distanciaCampo(Campo $campo, $latitud, $longitud): float
    {
        $coordenadasCampo = $this->resolverCoordenadasCampoDirecto($campo);

        if (!$latitud || !$longitud || !$coordenadasCampo) {
            return 999999;
        }

        $lat1 = deg2rad((float) $latitud);
        $lon1 = deg2rad((float) $longitud);
        $lat2 = deg2rad((float) $coordenadasCampo['latitud']);
        $lon2 = deg2rad((float) $coordenadasCampo['longitud']);

        $dLat = $lat2 - $lat1;
        $dLon = $lon2 - $lon1;
        $a = sin($dLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dLon / 2) ** 2;

        return 6371 * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    private function resolverCoordenadasUsuario($latitud, $longitud, ?string $ciudad, string $modo): ?array
    {
        if ($modo === 'desde-ciudad') {
            return $this->coordenadasPorCiudad($ciudad);
        }

        if ($latitud !== null && $latitud !== '' && $longitud !== null && $longitud !== '') {
            return [
                'latitud' => (float) $latitud,
                'longitud' => (float) $longitud,
            ];
        }

        return $this->coordenadasPorCiudad($ciudad);
    }

    private function resolverCoordenadasCampo(Partido $partido): ?array
    {
        return $partido->campo ? $this->resolverCoordenadasCampoDirecto($partido->campo) : null;
    }

    private function resolverCoordenadasCampoDirecto(Campo $campo): ?array
    {
        if ($campo->latitud && $campo->longitud) {
            return [
                'latitud' => (float) $campo->latitud,
                'longitud' => (float) $campo->longitud,
            ];
        }

        return $this->coordenadasPorCiudad($campo->ciudad);
    }

    private function coordenadasPorCiudad(?string $ciudad): ?array
    {
        $ciudades = [
            'monovar' => ['latitud' => 38.4386, 'longitud' => -0.8404],
            'monover' => ['latitud' => 38.4386, 'longitud' => -0.8404],
            'villena' => ['latitud' => 38.6373, 'longitud' => -0.8657],
            'alicante' => ['latitud' => 38.3452, 'longitud' => -0.4810],
            'elche' => ['latitud' => 38.2699, 'longitud' => -0.7126],
            'elda' => ['latitud' => 38.4778, 'longitud' => -0.7917],
            'petrer' => ['latitud' => 38.4789, 'longitud' => -0.7906],
            'novelda' => ['latitud' => 38.3848, 'longitud' => -0.7677],
            'aspe' => ['latitud' => 38.3451, 'longitud' => -0.7672],
            'sax' => ['latitud' => 38.5372, 'longitud' => -0.8176],
            'biar' => ['latitud' => 38.6308, 'longitud' => -0.7647],
            'castalla' => ['latitud' => 38.5969, 'longitud' => -0.6723],
            'ibi' => ['latitud' => 38.6253, 'longitud' => -0.5723],
            'alcoy' => ['latitud' => 38.6983, 'longitud' => -0.4743],
            'alcoi' => ['latitud' => 38.6983, 'longitud' => -0.4743],
            'sanvicentedelraspeig' => ['latitud' => 38.3964, 'longitud' => -0.5255],
            'santvicentdelraspeig' => ['latitud' => 38.3964, 'longitud' => -0.5255],
            'benidorm' => ['latitud' => 38.5411, 'longitud' => -0.1225],
            'torrevieja' => ['latitud' => 37.9787, 'longitud' => -0.6822],
        ];

        return $ciudades[$this->normalizarUbicacion($ciudad)] ?? null;
    }

    private function normalizarUbicacion(?string $texto): string
    {
        return Str::of($texto ?? '')
            ->ascii()
            ->lower()
            ->trim()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->toString();
    }

    private function estaDentroDeVentanaActiva(Partido $partido): bool
    {
        $limite = $this->limiteActivoPartido($partido);

        return !$limite || now()->lte($limite);
    }

    private function coincideFiltroFecha(Partido $partido, string $modoFecha, ?string $fecha): bool
    {
        if ($modoFecha === 'aleatorio') {
            return true;
        }

        if (!$partido->fecha) {
            return false;
        }

        $fechaPartido = Carbon::parse($partido->fecha)->toDateString();

        return match ($modoFecha) {
            'hoy' => $fechaPartido === now()->toDateString(),
            'manana' => $fechaPartido === now()->addDay()->toDateString(),
            'fecha' => $fecha && $fechaPartido === Carbon::parse($fecha)->toDateString(),
            'finde' => in_array(Carbon::parse($fechaPartido)->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY], true)
                && Carbon::parse($fechaPartido)->betweenIncluded(now()->startOfDay(), now()->copy()->next(Carbon::SUNDAY)->endOfDay()),
            default => true,
        };
    }

    private function estaEnHistorial(Partido $partido): bool
    {
        $limite = $this->limiteActivoPartido($partido);

        return $limite && now()->gt($limite);
    }

    private function limiteActivoPartido(Partido $partido): ?Carbon
    {
        if (!$partido->fecha || !$partido->hora) {
            return null;
        }

        return Carbon::parse($partido->fecha . ' ' . $partido->hora)->addHours(24);
    }

    private function sincronizarEstadoPorPlazas(Partido $partido): void
    {
        if (in_array($partido->estado, ['cancelado', 'finalizado'], true)) {
            return;
        }

        $confirmados = $partido->usuarios()
            ->wherePivot('estado_participacion', 'confirmado')
            ->count();

        $partido->estado = $confirmados >= $this->capacidadTotal($partido) ? 'completo' : 'abierto';
        $partido->save();
    }

    private function invitarUsuarioAlPartido(Partido $partido, Usuario $usuario): void
    {
        $equipoAsignado = $this->elegirEquipo($partido);

        $partido->usuarios()->attach($usuario->id_usuario, [
            'estado_participacion' => 'pendiente',
            'equipo_asignado' => $equipoAsignado,
            'posicion_asignada' => $this->elegirPosicion($usuario->posiciones_favoritas, $partido, $equipoAsignado),
            'es_capitan' => false
        ]);
    }

    private function encontrarPartidoCompetitivo(Request $request, string $tipoFutbol, string $fecha, ?int $idEquipo, string $hora, bool $usarProximidad = false, ?float $radio = null): ?Partido
    {
        $coordenadasUsuario = $usarProximidad
            ? $this->resolverCoordenadasUsuario(null, null, $request->user()->ciudad, 'desde-ciudad')
            : null;

        if ($usarProximidad && !$coordenadasUsuario) {
            abort(response()->json([
                'mensaje' => 'Añade tu ubicación en el perfil para buscar por proximidad.'
            ], 422));
        }

        return Partido::withCount(['usuariosConfirmados as usuarios_count'])
            ->with('campo')
            ->where('es_publico', true)
            ->where('nivel', 'Competitivo')
            ->where('tipo_futbol', $tipoFutbol)
            ->where('fecha', $fecha)
            ->where('hora', $hora)
            ->where(function ($query) {
                $query->where('estado', '!=', 'cancelado')
                    ->orWhereNull('estado');
            })
            ->orderBy('fecha')
            ->orderBy('hora')
            ->get()
            ->filter(function ($partido) use ($usarProximidad, $coordenadasUsuario, $radio) {
                if (!$usarProximidad) {
                    return true;
                }

                $distancia = $this->distanciaPartido($partido, $coordenadasUsuario['latitud'], $coordenadasUsuario['longitud']);

                return $distancia < 999999 && (!$radio || $distancia <= $radio);
            })
            ->sortBy(function ($partido) use ($usarProximidad, $coordenadasUsuario) {
                if (!$usarProximidad) {
                    return 0;
                }

                return $this->distanciaPartido($partido, $coordenadasUsuario['latitud'], $coordenadasUsuario['longitud']);
            })
            ->first(function ($partido) use ($idEquipo) {
                return $partido->usuarios_count < $this->capacidadTotal($partido)
                    && $this->puedeAsignarEquipoCompetitivo($partido, $idEquipo);
            });
    }

    private function crearPartidoCompetitivo(Request $request, string $tipoFutbol, string $fecha, ?int $idEquipo, string $hora, bool $usarProximidad = false, ?float $radio = null): ?Partido
    {
        $campo = $this->campoCompetitivoMasCercano($request, $usarProximidad, $radio);

        if (!$campo) {
            return null;
        }

        $datos = [
            'titulo' => 'Partido competitivo',
            'fecha' => $fecha,
            'hora' => $hora,
            'tipo_futbol' => $tipoFutbol,
            'nivel' => 'Competitivo',
            'estado' => 'abierto',
            'es_publico' => true,
            'codigo_acceso' => strtoupper(Str::random(6)),
            'id_creador' => $request->user()->id_usuario,
            'id_campo' => $campo->id_campo,
            'id_equipo_local' => $idEquipo,
            'jugadores_minimos' => $this->jugadoresMinimos((object) ['tipo_futbol' => $tipoFutbol]),
            'es_competitivo' => true
        ];

        $datos['plazas_totales'] = $this->capacidadTotal((object) $datos);

        return Partido::create($datos);
    }

    private function horaCompetitivaCoherente(string $fecha): string
    {
        if (Carbon::parse($fecha)->isToday()) {
            return now()->lt(now()->copy()->setTime(11, 0)) ? '11:00' : '20:00';
        }

        return '20:00';
    }

    private function campoCompetitivoMasCercano(Request $request, bool $usarProximidad, ?float $radio): ?Campo
    {
        if (!$usarProximidad) {
            return Campo::first();
        }

        $coordenadasUsuario = $this->resolverCoordenadasUsuario(null, null, $request->user()->ciudad, 'desde-ciudad');

        if (!$coordenadasUsuario) {
            abort(response()->json([
                'mensaje' => 'Añade tu ubicación en el perfil para buscar por proximidad.'
            ], 422));
        }

        return Campo::all()
            ->map(function ($campo) use ($coordenadasUsuario) {
                $distancia = $this->distanciaCampo($campo, $coordenadasUsuario['latitud'], $coordenadasUsuario['longitud']);
                $campo->distancia_km = $distancia >= 999999 ? null : round($distancia, 1);
                $campo->distancia_orden = $distancia;

                return $campo;
            })
            ->filter(function ($campo) use ($radio) {
                if ($campo->distancia_km === null) {
                    return false;
                }

                return !$radio || (float) $campo->distancia_km <= $radio;
            })
            ->sortBy('distancia_orden')
            ->first();
    }

    private function puedeAsignarEquipoCompetitivo(Partido $partido, ?int $idEquipo): bool
    {
        if (!$idEquipo) {
            return true;
        }

        return $partido->id_equipo_local === $idEquipo
            || $partido->id_equipo_visitante === $idEquipo
            || !$partido->id_equipo_local
            || !$partido->id_equipo_visitante;
    }

    private function asignarEquipoCompetitivo(Partido $partido, int $idEquipo): void
    {
        if ($partido->id_equipo_local === $idEquipo || $partido->id_equipo_visitante === $idEquipo) {
            return;
        }

        if (!$partido->id_equipo_local) {
            $partido->id_equipo_local = $idEquipo;
        } elseif (!$partido->id_equipo_visitante) {
            $partido->id_equipo_visitante = $idEquipo;
        }

        $partido->save();
    }

    private function capacidadTotal(object $partido): int
    {
        $capacidadPorTipo = $this->jugadoresMinimos($partido) + 4;

        if (!empty($partido->plazas_totales)) {
            return max((int) $partido->plazas_totales, $capacidadPorTipo);
        }

        return $capacidadPorTipo;
    }

    private function capacidadPorEquipo(object $partido): int
    {
        return (int) ceil($this->capacidadTotal($partido) / 2);
    }

    private function titularesPorEquipo(object $partido): int
    {
        return $this->capacidadPorEquipo($partido);
    }

    private function jugadoresPorTipo(object $partido): int
    {
        $tipo = strtolower($partido->tipo_futbol ?? '');

        if (str_contains($tipo, '5v5') || str_contains($tipo, '5') || str_contains($tipo, 'sala')) {
            return 10;
        }

        if (str_contains($tipo, '7v7') || str_contains($tipo, '7')) {
            return 16;
        }

        return 22;
    }

    private function cancelarSiAlineacionesIncompletas(Partido $partido): void
    {
        if ($partido->estado === 'cancelado' || !$partido->fecha || !$partido->hora) {
            return;
        }

        $inicio = Carbon::parse($partido->fecha . ' ' . $partido->hora);

        if (now()->lt($inicio)) {
            return;
        }

        if ($this->jugadoresConfirmados($partido) < $this->jugadoresMinimos($partido)) {
            $partido->estado = 'cancelado';
            $partido->save();
        }
    }

    private function jugadoresConfirmados(Partido $partido): int
    {
        return $partido->usuarios()->wherePivot('estado_participacion', 'confirmado')->count();
    }

    private function jugadoresMinimos(object $partido): int
    {
        $jugadoresPorTipo = $this->jugadoresPorTipo($partido);

        if (!empty($partido->jugadores_minimos)) {
            return max((int) $partido->jugadores_minimos, $jugadoresPorTipo);
        }

        return $jugadoresPorTipo;
    }

    private function fechaLimiteResultado(Partido $partido): ?string
    {
        if (!$partido->fecha || !$partido->hora) {
            return null;
        }

        return Carbon::parse($partido->fecha . ' ' . $partido->hora)->addDay()->toDateTimeString();
    }

    private function ventanaResultadoAbierta(Partido $partido): bool
    {
        if (!$partido->fecha || !$partido->hora || $partido->estado === 'cancelado') {
            return false;
        }

        $inicio = Carbon::parse($partido->fecha . ' ' . $partido->hora);

        return now()->betweenIncluded($inicio, $inicio->copy()->addDay());
    }

    private function formacionesPorTipo(object $partido): array
    {
        $tipo = strtolower($partido->tipo_futbol ?? '');

        if (str_contains($tipo, '5v5') || str_contains($tipo, '5') || str_contains($tipo, 'sala')) {
            return ['1-2-1', '2-1-1', '2-2', '1-1-2'];
        }

        if (str_contains($tipo, '7')) {
            return ['3-3-1', '2-3-2', '3-2-2', '2-4-1', '2-1-3-1'];
        }

        return ['4-3-3', '4-3-1-2', '4-4-2', '3-5-2', '4-2-3-1'];
    }

    private function partidoHaEmpezado(Partido $partido): bool
    {
        if (!$partido->fecha || !$partido->hora) {
            return false;
        }

        return now()->gte(Carbon::parse($partido->fecha . ' ' . $partido->hora));
    }

    private function pasarCapitan(Partido $partido, string $equipo): void
    {
        $nuevoCapitan = $partido->usuarios()
            ->wherePivot('equipo_asignado', $equipo)
            ->first();

        if ($nuevoCapitan) {
            $partido->usuarios()->updateExistingPivot($nuevoCapitan->id_usuario, [
                'es_capitan' => true
            ]);
        }
    }

    private function elegirPosicion(?string $posicionesFavoritas, Partido $partido, string $equipo): string
    {
        $posicionesFormacion = $this->posicionesDeFormacion($this->formacionDelEquipo($partido, $equipo));
        $preferidas = $this->posicionesPreferidas($posicionesFavoritas, $partido);

        foreach ($preferidas as $posicion) {
            if ($this->posicionDisponible($partido, $equipo, $posicion, $posicionesFormacion)) {
                return $posicion;
            }
        }

        foreach (array_unique($posicionesFormacion) as $posicion) {
            if ($this->posicionDisponible($partido, $equipo, $posicion, $posicionesFormacion)) {
                return $posicion;
            }
        }

        return $posicionesFormacion[0] ?? 'MC';
    }

    private function posicionesPreferidas(?string $posicionesFavoritas, Partido $partido): array
    {
        $favoritas = array_map('trim', explode(',', $posicionesFavoritas ?? ''));
        $tipo = strtolower($partido->tipo_futbol ?? '');
        $esSala = str_contains($tipo, '5v5') || str_contains($tipo, '5') || str_contains($tipo, 'sala');
        $posiciones = [];

        foreach ($favoritas as $posicion) {
            $posiciones = array_merge($posiciones, match ($posicion) {
                'Portero' => ['POR'],
                'Defensa' => $esSala ? ['DFC'] : ['DFC', 'LI', 'LD'],
                'Mediocentro' => $esSala ? ['ALA'] : ['MC', 'MCD', 'MCO'],
                'Delantero' => $esSala ? ['PIV'] : ['DC', 'EI', 'ED'],
                default => [],
            });
        }

        return array_values(array_unique($posiciones));
    }

    private function posicionDisponible(Partido $partido, string $equipo, string $posicion, array $posicionesFormacion): bool
    {
        $maximo = array_count_values($posicionesFormacion)[$posicion] ?? 0;

        if ($maximo <= 0) {
            return false;
        }

        $ocupadas = $partido->usuarios()
            ->wherePivot('estado_participacion', 'confirmado')
            ->wherePivot('equipo_asignado', $equipo)
            ->wherePivot('posicion_asignada', $posicion)
            ->count();

        return $ocupadas < $maximo;
    }

    private function formacionDelEquipo(Partido $partido, string $equipo): string
    {
        $formacionesValidas = $this->formacionesPorTipo($partido);
        $formacion = $equipo === 'Equipo A'
            ? $partido->formacion_local
            : $partido->formacion_visitante;

        return in_array($formacion, $formacionesValidas, true)
            ? $formacion
            : $formacionesValidas[0];
    }

    private function posicionesDeFormacion(string $formacion): array
    {
        return match ($formacion) {
            '4-3-3' => ['POR', 'LI', 'DFC', 'DFC', 'LD', 'MC', 'MCD', 'MC', 'EI', 'DC', 'ED'],
            '4-3-1-2' => ['POR', 'LI', 'DFC', 'DFC', 'LD', 'MC', 'MCD', 'MC', 'MCO', 'DC', 'DC'],
            '4-4-2' => ['POR', 'LI', 'DFC', 'DFC', 'LD', 'MC', 'MCD', 'MC', 'MC', 'DC', 'DC'],
            '3-5-2' => ['POR', 'DFC', 'DFC', 'DFC', 'MC', 'MCD', 'MC', 'MCO', 'MC', 'DC', 'DC'],
            '4-2-3-1' => ['POR', 'LI', 'DFC', 'DFC', 'LD', 'MCD', 'MCD', 'EI', 'MCO', 'ED', 'DC'],
            '3-3-1' => ['POR', 'DFC', 'DFC', 'DFC', 'MC', 'MC', 'MC', 'DC'],
            '2-3-2' => ['POR', 'DFC', 'DFC', 'MC', 'MC', 'MC', 'DC', 'DC'],
            '3-2-2' => ['POR', 'DFC', 'DFC', 'DFC', 'MC', 'MC', 'DC', 'DC'],
            '2-4-1' => ['POR', 'DFC', 'DFC', 'MC', 'MCD', 'MC', 'MC', 'DC'],
            '2-1-3-1' => ['POR', 'DFC', 'DFC', 'MCD', 'MC', 'MCO', 'MC', 'DC'],
            '1-2-1' => ['POR', 'DFC', 'ALA', 'ALA', 'PIV'],
            '2-1-1' => ['POR', 'DFC', 'DFC', 'ALA', 'PIV'],
            '2-2' => ['POR', 'DFC', 'DFC', 'ALA', 'PIV'],
            '1-1-2' => ['POR', 'DFC', 'ALA', 'PIV', 'PIV'],
            default => ['POR', 'DFC', 'MC', 'DC'],
        };
    }

    private function esAdmin(Request $request): bool
    {
        return $request->user()?->rol === 'admin';
    }

    private function esPartidoCompetitivo(Partido $partido): bool
    {
        return (bool) $partido->es_competitivo || strtolower(trim($partido->nivel ?? '')) === 'competitivo';
    }

    private function usuarioPuedeInvitarASala(Request $request, Partido $partido): bool
    {
        if ($this->esAdmin($request)) {
            return true;
        }

        return $partido->usuarios()
            ->where('usuarios.id_usuario', $request->user()->id_usuario)
            ->wherePivot('estado_participacion', 'confirmado')
            ->exists();
    }

    private function usuarioTienePosicionFavorita(Usuario $usuario, string $posicion): bool
    {
        $favoritas = collect(explode(',', $usuario->posiciones_favoritas ?? ''))
            ->map(fn ($item) => Str::of($item)->ascii()->lower()->trim()->toString())
            ->filter()
            ->values();

        return $favoritas->contains(Str::of($posicion)->ascii()->lower()->trim()->toString());
    }

    private function sonAmigos(int $usuarioId, int $amigoId): bool
    {
        $columnaEmisor = Schema::hasColumn('amistades', 'id_usuario_emisor') ? 'id_usuario_emisor' : 'id_usuario';
        $columnaReceptor = Schema::hasColumn('amistades', 'id_usuario_receptor') ? 'id_usuario_receptor' : 'id_amigo';

        return Amistad::whereIn('estado', ['aceptada', 'aceptado'])
            ->where(function ($query) use ($usuarioId, $amigoId, $columnaEmisor, $columnaReceptor) {
                $query->where(function ($subquery) use ($usuarioId, $amigoId, $columnaEmisor, $columnaReceptor) {
                    $subquery->where($columnaEmisor, $usuarioId)
                        ->where($columnaReceptor, $amigoId);
                })->orWhere(function ($subquery) use ($usuarioId, $amigoId, $columnaEmisor, $columnaReceptor) {
                    $subquery->where($columnaEmisor, $amigoId)
                        ->where($columnaReceptor, $usuarioId);
                });
            })
            ->exists();
    }

    private function puedeEditarResultado(Request $request, Partido $partido): bool
    {
        if ($partido->estado === 'cancelado') {
            return false;
        }

        if (!$this->ventanaResultadoAbierta($partido)) {
            return false;
        }

        return $partido->usuarios()
            ->where('usuarios.id_usuario', $request->user()->id_usuario)
            ->wherePivot('estado_participacion', 'confirmado')
            ->wherePivot('es_capitan', true)
            ->exists();
    }
}

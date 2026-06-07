<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Amistad;
use App\Models\Estadistica;
use App\Models\RegistroPendiente;
use App\Models\Usuario;
use App\Models\VotoMvp;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use App\Mail\CodigoCambioContrasenaMail;

/**
 * Controlador que agrupa la logica de usuario en la API.
 */
class UsuarioController extends Controller
{
    /**
     * Devuelve el listado principal de recursos.
     */
    public function index()
    {
        return response()->json(Usuario::all());
    }

    /**
     * Devuelve todos los usuarios para la vista de administracion.
     */
    public function adminIndex(Request $request)
    {
        if ($request->user()?->rol !== 'admin') {
            return response()->json(['mensaje' => 'Solo el admin puede ver todos los usuarios'], 403);
        }

        return response()->json(
            Usuario::query()
                ->orderByDesc('id_usuario')
                ->get()
        );
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function buscar(Request $request)
    {
        $datos = $request->validate([
            'query' => 'required|string|min:1|max:60'
        ]);

        $usuarioId = $request->user()->id_usuario;
        $query = trim($datos['query']);
        $columnaEmisor = Schema::hasColumn('amistades', 'id_usuario_emisor') ? 'id_usuario_emisor' : 'id_usuario';
        $columnaReceptor = Schema::hasColumn('amistades', 'id_usuario_receptor') ? 'id_usuario_receptor' : 'id_amigo';

        $usuariosBloqueados = Amistad::whereIn('estado', ['pendiente', 'aceptada', 'aceptado'])
            ->where(function ($consulta) use ($usuarioId, $columnaEmisor, $columnaReceptor) {
                $consulta->where($columnaEmisor, $usuarioId)
                    ->orWhere($columnaReceptor, $usuarioId);
            })
            ->get()
            ->map(function ($amistad) use ($usuarioId, $columnaEmisor, $columnaReceptor) {
                return (int) $amistad->{$columnaEmisor} === (int) $usuarioId
                    ? $amistad->{$columnaReceptor}
                    : $amistad->{$columnaEmisor};
            })
            ->values();

        $usuarios = Usuario::query()
            ->select('id_usuario', 'nombre_usuario', 'nombre', 'apellido', 'foto_perfil', 'ciudad')
            ->where('id_usuario', '!=', $usuarioId)
            ->whereNotIn('id_usuario', $usuariosBloqueados)
            ->where(function ($consulta) use ($query) {
                $consulta->where('nombre_usuario', 'like', "%{$query}%")
                    ->orWhere('nombre', 'like', "%{$query}%");
            })
            ->orderBy('nombre_usuario')
            ->limit(8)
            ->get();

        return response()->json($usuarios);
    }

    /**
     * Gestiona informacion de usuarios.
     */
    public function comprobarNombreUsuario(Request $request)
    {
        $datos = $request->validate([
            'nombre_usuario' => 'required|string|max:60'
        ]);

        $nombreUsuario = trim($datos['nombre_usuario']);
        $usuario = $request->user();
        $enUso = Usuario::where('nombre_usuario', $nombreUsuario)
            ->when($usuario, fn ($query) => $query->where('id_usuario', '!=', $usuario->id_usuario))
            ->exists();
        $pendiente = RegistroPendiente::where('nombre_usuario', $nombreUsuario)
            ->where('codigo_expira_en', '>', now())
            ->exists();
        $noDisponible = $enUso || $pendiente;

        return response()->json([
            'disponible' => !$noDisponible,
            'mensaje' => $noDisponible
                ? 'Ese nombre de usuario ya está en uso.'
                : 'Nombre de usuario disponible.'
        ]);
    }

    /**
     * Guarda un nuevo recurso con los datos recibidos.
     */
    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre_usuario' => 'required|string|max:60|unique:usuarios,nombre_usuario',
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:usuarios,email',
            'contrasena' => 'required|string|min:6',
            'ciudad' => 'nullable|string|max:100',
            'posiciones_favoritas' => 'nullable|string|max:255',
            'rol' => 'required|in:usuario,admin'
        ], [
            'nombre_usuario.unique' => 'Ese nombre de usuario ya está en uso.',
            'email.unique' => 'Ese email ya está en uso.'
        ]);

        $datos['contrasena'] = Hash::make($datos['contrasena']);
        $datos['fecha_registro'] = now()->toDateString();

        $usuario = Usuario::create($datos);

        return response()->json($usuario, 201);
    }

    /**
     * Devuelve el detalle del recurso solicitado.
     */
    public function show($id)
    {
        $usuario = Usuario::with(['competitivo', 'estadisticas'])->findOrFail($id);
        $estadisticas = $usuario->estadisticas ?: new Estadistica([
            'id_usuario' => $usuario->id_usuario,
            'partidos_jugados' => 0,
            'partidos_ganados' => 0,
            'partidos_perdidos' => 0,
            'goles' => 0,
            'asistencias' => 0,
            'porterias_cero' => 0,
            'tarjetas_amarillas' => 0,
            'tarjetas_rojas' => 0
        ]);

        $estadisticas->mvps = $this->contarMvpsUsuario($usuario->id_usuario);
        $usuario->setRelation('estadisticas', $estadisticas);

        return response()->json($usuario);
    }

    /**
     * Actualiza los datos del recurso indicado.
     */
    public function update(Request $request, $id)
    {
        $usuario = Usuario::findOrFail($id);
        $datos = $request->validate([
            'nombre_usuario' => [
                'required',
                'string',
                'max:60',
                Rule::unique('usuarios', 'nombre_usuario')->ignore($usuario->id_usuario, 'id_usuario')
            ],
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('usuarios', 'email')->ignore($usuario->id_usuario, 'id_usuario')
            ],
            'contrasena' => 'nullable|string|min:6',
            'ciudad' => 'nullable|string|max:100',
            'posiciones_favoritas' => 'nullable|string|max:255',
            'rol' => 'required|in:usuario,admin'
        ], [
            'nombre_usuario.unique' => 'Ese nombre de usuario ya está en uso.',
            'email.unique' => 'Ese email ya está en uso.'
        ]);

        if (!empty($datos['contrasena'])) {
            $datos['contrasena'] = Hash::make($datos['contrasena']);
        } else {
            unset($datos['contrasena']);
        }

        $usuario->update($datos);

        return response()->json($usuario);
    }

    /**
     * Devuelve el detalle del recurso solicitado.
     */
    public function actualizarPerfil(Request $request)
    {
        $usuario = $request->user();

        $datos = $request->validate([
            'nombre_usuario' => [
                'required',
                'string',
                'max:60',
                Rule::unique('usuarios', 'nombre_usuario')->ignore($usuario->id_usuario, 'id_usuario')
            ],
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'ciudad' => 'nullable|string|max:100',
            'posiciones_favoritas' => 'nullable|string|max:255'
        ], [
            'nombre_usuario.unique' => 'Ese nombre de usuario ya está en uso.'
        ]);

        $usuario->update($datos);

        return response()->json($usuario);
    }

    /**
     * Actualiza los datos del recurso indicado.
     */
    public function actualizarContrasena(Request $request)
    {
        $usuario = $request->user();

        $datos = $request->validate([
            'contrasena_actual' => 'required|string',
            'contrasena' => 'required|string|min:6|confirmed',
        ], [
            'contrasena_actual.required' => 'Introduce tu contraseña actual.',
            'contrasena.required' => 'Introduce la nueva contraseña.',
            'contrasena.min' => 'La nueva contraseña debe tener al menos 6 caracteres.',
            'contrasena.confirmed' => 'La confirmación no coincide con la nueva contraseña.',
        ]);

        if (!Hash::check($datos['contrasena_actual'], $usuario->contrasena)) {
            return response()->json([
                'mensaje' => 'La contraseña actual no es correcta.',
                'errors' => [
                    'contrasena_actual' => ['La contraseña actual no es correcta.']
                ]
            ], 422);
        }

        $usuario->update([
            'contrasena' => Hash::make($datos['contrasena'])
        ]);

        return response()->json([
            'mensaje' => 'Contraseña actualizada correctamente.'
        ]);
    }

    /**
     * Solicita una accion pendiente y prepara su codigo o notificacion.
     */
    public function solicitarCodigoCambioContrasena(Request $request)
    {
        $usuario = $request->user();
        $codigo = $this->generarCodigoContrasena();

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $usuario->email],
            [
                'token' => Hash::make($codigo),
                'created_at' => now(),
            ]
        );

        Mail::to($usuario->email)->send(new CodigoCambioContrasenaMail($codigo));

        return response()->json([
            'mensaje' => 'Te hemos enviado un código a tu correo para cambiar la contraseña.'
        ]);
    }

    /**
     * Actualiza los datos del recurso indicado.
     */
    public function actualizarContrasenaConCodigo(Request $request)
    {
        $usuario = $request->user();

        $datos = $request->validate([
            'codigo' => 'required|digits:6',
            'contrasena' => 'required|string|min:6|confirmed',
        ], [
            'codigo.required' => 'Introduce el código enviado a tu correo.',
            'codigo.digits' => 'El código debe tener 6 dígitos.',
            'contrasena.required' => 'Introduce la nueva contraseña.',
            'contrasena.min' => 'La nueva contraseña debe tener al menos 6 caracteres.',
            'contrasena.confirmed' => 'La confirmación no coincide con la nueva contraseña.',
        ]);

        $registro = DB::table('password_reset_tokens')
            ->where('email', $usuario->email)
            ->first();

        if (!$registro || Carbon::parse($registro->created_at)->addMinutes(10)->isPast() || !Hash::check($datos['codigo'], $registro->token)) {
            return response()->json([
                'mensaje' => 'El código no es válido o ha caducado.',
                'errors' => [
                    'codigo' => ['El código no es válido o ha caducado.']
                ]
            ], 422);
        }

        $usuario->update([
            'contrasena' => Hash::make($datos['contrasena'])
        ]);

        DB::table('password_reset_tokens')->where('email', $usuario->email)->delete();

        return response()->json([
            'mensaje' => 'Contraseña actualizada correctamente.'
        ]);
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    private function generarCodigoContrasena(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Gestiona datos relacionados con amigos y solicitudes.
     */
    public function amigos(Request $request)
    {
        $amigos = $request->user()
            ->belongsToMany(Usuario::class, 'amistades', 'id_usuario', 'id_amigo')
            ->get();

        return response()->json($amigos);
    }

    /**
     * Gestiona datos relacionados con amigos y solicitudes.
     */
    public function agregarAmigo(Request $request)
    {
        $request->validate([
            'nombre_usuario' => 'required|string'
        ]);

        $usuario = $request->user();
        $amigo = Usuario::where('nombre_usuario', $request->nombre_usuario)->first();

        if (!$amigo || $amigo->id_usuario === $usuario->id_usuario) {
            return response()->json(['mensaje' => 'Usuario no válido'], 422);
        }

        $usuario->belongsToMany(Usuario::class, 'amistades', 'id_usuario', 'id_amigo')
            ->syncWithoutDetaching([
                $amigo->id_usuario => [
                    'estado' => 'aceptado',
                    'fecha_creacion' => now()
                ]
            ]);

        return response()->json(['mensaje' => 'Amigo agregado correctamente']);
    }

    /**
     * Elimina el recurso indicado cuando el usuario tiene permiso.
     */
    public function destroy(Request $request, $id)
    {
        if ((int) $request->user()->id_usuario === (int) $id) {
            return response()->json(['mensaje' => 'No puedes eliminar tu propio usuario administrador'], 422);
        }

        Usuario::findOrFail($id)->delete();
        return response()->json(['mensaje' => 'Usuario eliminado']);
    }

    /**
     * Gestiona votos de MVP.
     */
    private function contarMvpsUsuario(int $idUsuario): int
    {
        $votosPorPartido = VotoMvp::query()
            ->join('partidos', 'partidos.id_partido', '=', 'votos_mvp.id_partido')
            ->whereNotNull('partidos.fecha')
            ->whereNotNull('partidos.hora')
            ->where(function ($query) {
                $query->whereRaw("LOWER(TRIM(COALESCE(partidos.estado, ''))) != ?", ['cancelado'])
                    ->orWhereNull('partidos.estado');
            })
            ->whereRaw('DATE_ADD(TIMESTAMP(partidos.fecha, partidos.hora), INTERVAL 24 HOUR) < ?', [now()])
            ->selectRaw('votos_mvp.id_partido, votos_mvp.id_usuario_votado, SUM(votos_mvp.peso_voto) as puntos')
            ->groupBy('votos_mvp.id_partido', 'votos_mvp.id_usuario_votado')
            ->get()
            ->groupBy('id_partido');

        return $votosPorPartido->filter(function ($rankingPartido) use ($idUsuario) {
            $maxPuntos = $rankingPartido->max('puntos');

            return $rankingPartido->contains(function ($fila) use ($idUsuario, $maxPuntos) {
                return (int) $fila->id_usuario_votado === (int) $idUsuario
                    && (int) $fila->puntos === (int) $maxPuntos;
            });
        })->count();
    }
}

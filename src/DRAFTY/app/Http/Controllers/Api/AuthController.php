<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Mail\CodigoCambioContrasenaMail;
use App\Mail\CodigoVerificacionMail;
use App\Models\Competitivo;
use App\Models\RegistroPendiente;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Controlador que agrupa la logica de auth en la API.
 */
class AuthController extends Controller
{
    /**
     * Registra un nuevo usuario en la aplicacion.
     */
    public function register(RegisterRequest $request)
    {
        $datos = $request->validated();
        $datos['email'] = strtolower($datos['email']);
        $codigo = $this->generarCodigoVerificacion();

        $registroPendiente = RegistroPendiente::where('email', $datos['email'])
            ->orWhere('nombre_usuario', $datos['nombre_usuario'])
            ->first();

        if ($registroPendiente && $registroPendiente->codigo_expira_en->isFuture()) {
            $campoPendiente = $registroPendiente->email === $datos['email'] ? 'email' : 'nombre_usuario';

            return response()->json([
                'mensaje' => 'Ya hay un registro pendiente con ese email o nombre de usuario. Revisa el correo o solicita reenviar el código.',
                'errors' => [
                    $campoPendiente => [
                        $campoPendiente === 'email'
                            ? 'Ese email ya tiene una verificacion pendiente.'
                            : 'Ese nombre de usuario ya está en uso.'
                    ]
                ]
            ], 422);
        }

        $registroPendiente?->delete();

        $registro = RegistroPendiente::create([
            'nombre_usuario' => $datos['nombre_usuario'],
            'nombre' => $datos['nombre'],
            'apellido' => $datos['apellido'],
            'email' => $datos['email'],
            'contrasena' => Hash::make($datos['contrasena']),
            'ciudad' => $datos['ciudad'] ?? null,
            'posiciones_favoritas' => $datos['posiciones_favoritas'] ?? null,
            'codigo_verificacion' => $codigo,
            'codigo_expira_en' => now()->addMinutes(10),
            'intentos' => 0,
        ]);

        try {
            $this->enviarCodigo($datos['email'], $codigo);
        } catch (Throwable $error) {
            report($error);

            return response()->json([
                'mensaje' => 'Cuenta pendiente creada, pero no se ha podido enviar el código. Revisa la configuracion SMTP y pulsa reenviar codigo.',
                'email' => $datos['email']
            ], 503);
        }

        return response()->json([
            'mensaje' => 'Código enviado al correo',
            'email' => $datos['email']
        ], 202);
    }

    /**
     * Comprueba el codigo de verificacion enviado al usuario.
     */
    public function verificarCodigo(Request $request)
    {
        $datos = $request->validate([
            'email' => 'required|email',
            'codigo' => 'required|digits:6',
        ]);
        $datos['email'] = strtolower($datos['email']);

        $registro = RegistroPendiente::where('email', $datos['email'])->first();

        if (!$registro) {
            return response()->json(['mensaje' => 'No hay ningún registro pendiente para este email'], 404);
        }

        if ($registro->codigo_expira_en->isPast()) {
            return response()->json(['mensaje' => 'El código ha expirado. Solicita uno nuevo.'], 422);
        }

        if ($registro->intentos >= 5) {
            return response()->json(['mensaje' => 'Has superado el limite de intentos. Solicita un código nuevo.'], 429);
        }

        if ($registro->codigo_verificacion !== $datos['codigo']) {
            $registro->increment('intentos');

            return response()->json(['mensaje' => 'Código incorrecto'], 422);
        }

        if (Usuario::where('email', $registro->email)->exists()) {
            $registro->delete();

            return response()->json(['mensaje' => 'Ese email ya esta registrado'], 422);
        }

        if (Usuario::where('nombre_usuario', $registro->nombre_usuario)->exists()) {
            $registro->delete();

            return response()->json(['mensaje' => 'Ese nombre de usuario ya esta registrado'], 422);
        }

        $usuario = Usuario::create([
            'nombre_usuario' => $registro->nombre_usuario,
            'nombre' => $registro->nombre,
            'apellido' => $registro->apellido,
            'email' => $registro->email,
            'contrasena' => $registro->contrasena,
            'fecha_registro' => now(),
            'ciudad' => $registro->ciudad,
            'posiciones_favoritas' => $registro->posiciones_favoritas,
        ]);

        Competitivo::firstOrCreate(
            ['id_usuario' => $usuario->id_usuario],
            [
                'rango' => 'Bronce 1',
                'puntos_competitivos' => 0,
                'activo' => false,
                'precio_mensual' => 3.99,
                'estado_pago' => 'pendiente',
                'fecha_actualizacion' => now()
            ]
        );

        $registro->delete();

        $token = $usuario->createToken('auth_token')->plainTextToken;

        return response()->json([
            'mensaje' => 'Correo verificado correctamente',
            'usuario' => $usuario,
            'token' => $token
        ], 201);
    }

    /**
     * Reenvia el codigo necesario al usuario.
     */
    public function reenviarCodigo(Request $request)
    {
        $datos = $request->validate([
            'email' => 'required|email',
        ]);
        $datos['email'] = strtolower($datos['email']);

        $registro = RegistroPendiente::where('email', $datos['email'])->first();

        if (!$registro) {
            return response()->json(['mensaje' => 'No hay ningún registro pendiente para este email'], 404);
        }

        $codigo = $this->generarCodigoVerificacion();
        $registro->update([
            'codigo_verificacion' => $codigo,
            'codigo_expira_en' => now()->addMinutes(10),
            'intentos' => 0,
        ]);

        try {
            $this->enviarCodigo($registro->email, $codigo);
        } catch (Throwable) {
            return response()->json([
                'mensaje' => 'No se ha podido reenviar el código. Revisa la configuracion SMTP del correo.'
            ], 503);
        }

        return response()->json([
            'mensaje' => 'Código reenviado al correo',
            'email' => $registro->email
        ]);
    }

    /**
     * Solicita una accion pendiente y prepara su codigo o notificacion.
     */
    public function solicitarCodigoRecuperacion(Request $request)
    {
        $datos = $request->validate([
            'email' => 'required|email',
        ]);
        $email = strtolower($datos['email']);
        $usuario = Usuario::where('email', $email)->first();

        if ($usuario) {
            $codigo = $this->generarCodigoVerificacion();

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'token' => Hash::make($codigo),
                    'created_at' => now(),
                ]
            );

            try {
                Mail::to($email)->send(new CodigoCambioContrasenaMail($codigo));
            } catch (Throwable $error) {
                report($error);

                return response()->json([
                    'mensaje' => 'No se ha podido enviar el código. Revisa la configuracion del correo.'
                ], 503);
            }
        }

        return response()->json([
            'mensaje' => 'Si el email pertenece a una cuenta de DRAFTY, recibirás un código para cambiar la contraseña.'
        ]);
    }

    /**
     * Permite recuperar la cuenta despues de validar el codigo.
     */
    public function recuperarContrasena(Request $request)
    {
        $datos = $request->validate([
            'email' => 'required|email',
            'codigo' => 'required|digits:6',
            'contrasena' => 'required|string|min:6|confirmed',
        ], [
            'codigo.required' => 'Introduce el código enviado a tu correo.',
            'codigo.digits' => 'El código debe tener 6 dígitos.',
            'contrasena.required' => 'Introduce la nueva contraseña.',
            'contrasena.min' => 'La nueva contraseña debe tener al menos 6 caracteres.',
            'contrasena.confirmed' => 'La confirmación no coincide con la nueva contraseña.',
        ]);
        $email = strtolower($datos['email']);
        $usuario = Usuario::where('email', $email)->first();
        $registro = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$usuario || !$registro || Carbon::parse($registro->created_at)->addMinutes(10)->isPast() || !Hash::check($datos['codigo'], $registro->token)) {
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

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return response()->json([
            'mensaje' => 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.'
        ]);
    }

    /**
     * Inicia sesion con email o nombre de usuario.
     *
     * Si el identificador contiene "@", se busca por email.
     * En caso contrario, se busca por nombre_usuario.
     *
     * @param LoginRequest $request Peticion validada con identificador y contrasena.
     * @return \Illuminate\Http\JsonResponse Respuesta con usuario y token de Sanctum.
     */
    public function login(LoginRequest $request)
    {
        $identificador = trim((string) ($request->input('identificador') ?? $request->input('email')));

        if ($identificador === '') {
            return response()->json([
                'mensaje' => 'Introduce tu usuario o email'
            ], 422);
        }

        $usuario = str_contains($identificador, '@')
            ? Usuario::where('email', strtolower($identificador))->first()
            : Usuario::where('nombre_usuario', $identificador)->first();

        if (!$usuario || !Hash::check($request->contrasena, $usuario->contrasena)) {
            return response()->json([
                'mensaje' => 'Credenciales incorrectas'
            ], 401);
        }

        $token = $usuario->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login correcto',
            'mensaje' => 'Login correcto',
            'usuario' => $usuario,
            'user' => $usuario,
            'token' => $token,
            'access_token' => $token,
            'auth_token' => $token,
            'token_type' => 'Bearer'
        ]);
    }

    /**
     * Devuelve el detalle del recurso solicitado.
     */
    public function perfil(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Cierra la sesion activa del usuario.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'mensaje' => 'Sesión cerrada correctamente'
        ]);
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    private function generarCodigoVerificacion(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    private function enviarCodigo(string $email, string $codigo): void
    {
        Mail::to($email)->send(new CodigoVerificacionMail($codigo));
    }
}

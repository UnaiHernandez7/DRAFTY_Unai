<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Mail\CodigoVerificacionMail;
use App\Models\Competitivo;
use App\Models\RegistroPendiente;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AuthController extends Controller
{
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
                            : 'Ese nombre de usuario ya est? en uso.'
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

    public function login(LoginRequest $request)
    {
        $request->validate([
            'email' => 'required|email',
            'contrasena' => 'required'
        ]);

        $usuario = Usuario::where('email', $request->email)->first();

        if (!$usuario || !Hash::check($request->contrasena, $usuario->contrasena)) {
            return response()->json([
                'mensaje' => 'Credenciales incorrectas'
            ], 401);
        }

        $token = $usuario->createToken('auth_token')->plainTextToken;

        return response()->json([
            'mensaje' => 'Login correcto',
            'usuario' => $usuario,
            'token' => $token
        ]);
    }

    public function perfil(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'mensaje' => 'Sesión cerrada correctamente'
        ]);
    }

    private function generarCodigoVerificacion(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function enviarCodigo(string $email, string $codigo): void
    {
        Mail::to($email)->send(new CodigoVerificacionMail($codigo));
    }
}

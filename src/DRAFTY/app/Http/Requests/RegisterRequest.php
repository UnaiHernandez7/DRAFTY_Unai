<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Peticion que valida los datos de register.
 */
class RegisterRequest extends FormRequest
{
    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower(trim((string) $this->input('email'))),
            ]);
        }
    }

    /**
     * Indica si la peticion puede ejecutar esta validacion.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Devuelve las reglas de validacion de la peticion.
     */
    public function rules(): array
    {
        return [
            'nombre_usuario' => 'required|string|max:60|unique:usuarios,nombre_usuario',
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'email' => 'required|email|unique:usuarios,email',
            'contrasena' => [
                'required',
                'string',
                'min:8',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! preg_match('/[a-z]/', $value) || ! preg_match('/[A-Z]/', $value)) {
                        $fail('La contraseña debe incluir mayúsculas y minúsculas.');
                    }

                    if (! preg_match('/[0-9]/', $value)) {
                        $fail('La contraseña debe incluir al menos un número.');
                    }

                    if (! preg_match('/[^A-Za-z0-9]/', $value)) {
                        $fail('La contraseña debe incluir al menos un símbolo.');
                    }
                }
            ],
            'ciudad' => 'nullable|string|max:100',
            'posiciones_favoritas' => 'nullable|string|max:255'
        ];
    }

    /**
     * Devuelve los mensajes personalizados de validacion.
     */
    public function messages(): array
    {
        return [
            'nombre_usuario.unique' => 'Ese nombre de usuario ya est? en uso.',
            'email.unique' => 'Ese email ya est? en uso.',
            'contrasena.min' => 'La contraseña debe tener al menos 8 caracteres.'
        ];
    }
}

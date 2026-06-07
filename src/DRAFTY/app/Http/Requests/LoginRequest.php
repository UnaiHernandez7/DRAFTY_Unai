<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Peticion que valida los datos de login.
 */
class LoginRequest extends FormRequest
{
    /**
     * Indica si la peticion puede ejecutar esta validacion.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define las reglas de validacion del login.
     *
     * Permite usar un identificador flexible para iniciar sesion
     * con nombre de usuario o email.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'identificador' => 'nullable|string|max:255',
            'email' => 'nullable|string|max:255',
            'contrasena' => 'required'
        ];
    }
}

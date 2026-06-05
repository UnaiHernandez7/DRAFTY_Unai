<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identificador' => 'required_without:email|string|max:255',
            'email' => 'required_without:identificador|string|max:255',
            'contrasena' => 'required'
        ];
    }
}

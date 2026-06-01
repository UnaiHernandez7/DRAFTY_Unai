<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistroPendiente extends Model
{
    protected $table = 'registros_pendientes';
    protected $primaryKey = 'id_registro';

    protected $fillable = [
        'nombre_usuario',
        'nombre',
        'apellido',
        'email',
        'contrasena',
        'ciudad',
        'posiciones_favoritas',
        'codigo_verificacion',
        'codigo_expira_en',
        'intentos',
    ];

    protected $casts = [
        'codigo_expira_en' => 'datetime',
    ];

    protected $hidden = [
        'contrasena',
        'codigo_verificacion',
    ];
}

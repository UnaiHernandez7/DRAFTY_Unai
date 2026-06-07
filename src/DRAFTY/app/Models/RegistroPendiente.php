<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa registropendiente dentro de la base de datos.
 */
class RegistroPendiente extends Model
{
    /**
     * Nombre de la tabla asociada al modelo.
     */
    protected $table = 'registros_pendientes';
    /**
     * Clave primaria usada por el modelo.
     */
    protected $primaryKey = 'id_registro';

    /**
     * Campos que se pueden rellenar de forma masiva.
     */
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

    /**
     * Conversiones de tipo aplicadas a campos del modelo.
     */
    protected $casts = [
        'codigo_expira_en' => 'datetime',
    ];

    /**
     * Campos ocultos cuando el modelo se convierte a respuesta.
     */
    protected $hidden = [
        'contrasena',
        'codigo_verificacion',
    ];
}

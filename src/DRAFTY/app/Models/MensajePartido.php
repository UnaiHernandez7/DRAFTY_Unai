<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa mensajepartido dentro de la base de datos.
 */
class MensajePartido extends Model
{
    /**
     * Nombre de la tabla asociada al modelo.
     */
    protected $table = 'mensajes_partido';
    /**
     * Clave primaria usada por el modelo.
     */
    protected $primaryKey = 'id_mensaje';

    /**
     * Indica si el modelo usa marcas de tiempo automaticas.
     */
    public $timestamps = false;

    /**
     * Campos que se pueden rellenar de forma masiva.
     */
    protected $fillable = [
        'id_partido',
        'id_usuario',
        'mensaje',
        'fecha_envio'
    ];

    /**
     * Gestiona informacion de usuarios.
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}

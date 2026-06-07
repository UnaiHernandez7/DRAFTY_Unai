<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa amistad dentro de la base de datos.
 */
class Amistad extends Model
{
    /**
     * Nombre de la tabla asociada al modelo.
     */
    protected $table = 'amistades';
    /**
     * Clave primaria usada por el modelo.
     */
    protected $primaryKey = 'id_amistad';

    /**
     * Indica si el modelo usa marcas de tiempo automaticas.
     */
    public $timestamps = false;

    /**
     * Campos que se pueden rellenar de forma masiva.
     */
    protected $fillable = [
        'id_usuario_emisor',
        'id_usuario_receptor',
        'estado',
        'visto_por_receptor',
        'fecha_solicitud',
        'fecha_respuesta'
    ];

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function emisor()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_emisor');
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function receptor()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_receptor');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa mensajeequipo dentro de la base de datos.
 */
class MensajeEquipo extends Model
{
    /**
     * Nombre de la tabla asociada al modelo.
     */
    protected $table = 'mensajes_equipo';
    /**
     * Clave primaria usada por el modelo.
     */
    protected $primaryKey = 'id_mensaje';

    /**
     * Campos que se pueden rellenar de forma masiva.
     */
    protected $fillable = [
        'id_equipo',
        'id_usuario',
        'mensaje'
    ];

    /**
     * Gestiona informacion relacionada con equipos.
     */
    public function equipo()
    {
        return $this->belongsTo(Equipo::class, 'id_equipo');
    }

    /**
     * Gestiona informacion de usuarios.
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}

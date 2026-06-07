<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa valoracionjugador dentro de la base de datos.
 */
class ValoracionJugador extends Model
{
    /**
     * Nombre de la tabla asociada al modelo.
     */
    protected $table = 'valoraciones_jugador';
    /**
     * Clave primaria usada por el modelo.
     */
    protected $primaryKey = 'id_valoracion';

    /**
     * Campos que se pueden rellenar de forma masiva.
     */
    protected $fillable = [
        'id_partido',
        'id_usuario_valorado',
        'id_usuario_valorador',
        'puntuacion',
        'comentario'
    ];

    /**
     * Gestiona informacion relacionada con partidos.
     */
    public function partido()
    {
        return $this->belongsTo(Partido::class, 'id_partido');
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function valorado()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_valorado');
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function valorador()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_valorador');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa votomvp dentro de la base de datos.
 */
class VotoMvp extends Model
{
    /**
     * Nombre de la tabla asociada al modelo.
     */
    protected $table = 'votos_mvp';
    /**
     * Clave primaria usada por el modelo.
     */
    protected $primaryKey = 'id_voto';

    /**
     * Campos que se pueden rellenar de forma masiva.
     */
    protected $fillable = [
        'id_partido',
        'id_usuario_votante',
        'id_usuario_votado',
        'peso_voto'
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
    public function votante()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_votante');
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function votado()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_votado');
    }
}

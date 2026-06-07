<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa golpartido dentro de la base de datos.
 */
class GolPartido extends Model
{
    /**
     * Nombre de la tabla asociada al modelo.
     */
    protected $table = 'goles_partido';
    /**
     * Clave primaria usada por el modelo.
     */
    protected $primaryKey = 'id_gol';

    /**
     * Campos que se pueden rellenar de forma masiva.
     */
    protected $fillable = [
        'id_partido',
        'id_usuario',
        'id_equipo',
        'equipo_sala',
        'minuto'
    ];

    /**
     * Gestiona informacion relacionada con partidos.
     */
    public function partido()
    {
        return $this->belongsTo(Partido::class, 'id_partido');
    }

    /**
     * Gestiona informacion de usuarios.
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    /**
     * Gestiona informacion relacionada con equipos.
     */
    public function equipo()
    {
        return $this->belongsTo(Equipo::class, 'id_equipo');
    }
}

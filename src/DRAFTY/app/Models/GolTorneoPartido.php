<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa goltorneopartido dentro de la base de datos.
 */
class GolTorneoPartido extends Model
{
    /**
     * Nombre de la tabla asociada al modelo.
     */
    protected $table = 'goles_torneo_partido';
    /**
     * Clave primaria usada por el modelo.
     */
    protected $primaryKey = 'id_gol_torneo';

    /**
     * Campos que se pueden rellenar de forma masiva.
     */
    protected $fillable = [
        'id_torneo_partido',
        'id_torneo',
        'id_usuario',
        'id_equipo',
        'minuto',
    ];

    /**
     * Gestiona informacion relacionada con partidos.
     */
    public function partido()
    {
        return $this->belongsTo(TorneoPartido::class, 'id_torneo_partido');
    }

    /**
     * Gestiona informacion relacionada con torneos.
     */
    public function torneo()
    {
        return $this->belongsTo(Torneo::class, 'id_torneo');
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

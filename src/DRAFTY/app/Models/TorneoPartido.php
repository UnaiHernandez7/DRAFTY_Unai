<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa torneopartido dentro de la base de datos.
 */
class TorneoPartido extends Model
{
    /**
     * Nombre de la tabla asociada al modelo.
     */
    protected $table = 'torneo_partidos';
    /**
     * Clave primaria usada por el modelo.
     */
    protected $primaryKey = 'id_torneo_partido';

    /**
     * Campos que se pueden rellenar de forma masiva.
     */
    protected $fillable = [
        'id_torneo',
        'ronda',
        'id_equipo_local',
        'id_equipo_visitante',
        'goles_local',
        'goles_visitante',
        'id_equipo_ganador',
        'estado',
        'fecha_partido'
    ];

    /**
     * Gestiona informacion relacionada con torneos.
     */
    public function torneo()
    {
        return $this->belongsTo(Torneo::class, 'id_torneo');
    }

    /**
     * Gestiona informacion relacionada con equipos.
     */
    public function equipoLocal()
    {
        return $this->belongsTo(Equipo::class, 'id_equipo_local');
    }

    /**
     * Gestiona informacion relacionada con equipos.
     */
    public function equipoVisitante()
    {
        return $this->belongsTo(Equipo::class, 'id_equipo_visitante');
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function ganador()
    {
        return $this->belongsTo(Equipo::class, 'id_equipo_ganador');
    }

    /**
     * Gestiona goles registrados.
     */
    public function goles()
    {
        return $this->hasMany(GolTorneoPartido::class, 'id_torneo_partido');
    }
}

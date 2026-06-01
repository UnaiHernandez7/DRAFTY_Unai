<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TorneoPartido extends Model
{
    protected $table = 'torneo_partidos';
    protected $primaryKey = 'id_torneo_partido';

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

    public function torneo()
    {
        return $this->belongsTo(Torneo::class, 'id_torneo');
    }

    public function equipoLocal()
    {
        return $this->belongsTo(Equipo::class, 'id_equipo_local');
    }

    public function equipoVisitante()
    {
        return $this->belongsTo(Equipo::class, 'id_equipo_visitante');
    }

    public function ganador()
    {
        return $this->belongsTo(Equipo::class, 'id_equipo_ganador');
    }

    public function goles()
    {
        return $this->hasMany(GolTorneoPartido::class, 'id_torneo_partido');
    }
}

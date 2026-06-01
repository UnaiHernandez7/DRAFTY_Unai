<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GolTorneoPartido extends Model
{
    protected $table = 'goles_torneo_partido';
    protected $primaryKey = 'id_gol_torneo';

    protected $fillable = [
        'id_torneo_partido',
        'id_torneo',
        'id_usuario',
        'id_equipo',
        'minuto',
    ];

    public function partido()
    {
        return $this->belongsTo(TorneoPartido::class, 'id_torneo_partido');
    }

    public function torneo()
    {
        return $this->belongsTo(Torneo::class, 'id_torneo');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function equipo()
    {
        return $this->belongsTo(Equipo::class, 'id_equipo');
    }
}

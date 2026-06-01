<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadisticaTorneoUsuario extends Model
{
    protected $table = 'estadisticas_torneo_usuario';
    protected $primaryKey = 'id_estadistica_torneo_usuario';

    protected $fillable = [
        'id_torneo',
        'id_usuario',
        'id_equipo',
        'goles',
        'asistencias',
        'porterias_cero',
        'partidos_jugados'
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function equipo()
    {
        return $this->belongsTo(Equipo::class, 'id_equipo');
    }
}

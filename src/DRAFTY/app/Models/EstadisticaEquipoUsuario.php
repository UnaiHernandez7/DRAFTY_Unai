<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadisticaEquipoUsuario extends Model
{
    protected $table = 'estadisticas_equipo_usuario';
    protected $primaryKey = 'id_estadistica_equipo_usuario';

    protected $fillable = [
        'id_equipo',
        'id_usuario',
        'partidos_jugados',
        'goles',
        'asistencias',
        'porterias_cero'
    ];

    public function equipo()
    {
        return $this->belongsTo(Equipo::class, 'id_equipo');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}

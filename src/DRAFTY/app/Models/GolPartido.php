<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GolPartido extends Model
{
    protected $table = 'goles_partido';
    protected $primaryKey = 'id_gol';

    protected $fillable = [
        'id_partido',
        'id_usuario',
        'id_equipo',
        'equipo_sala',
        'minuto'
    ];

    public function partido()
    {
        return $this->belongsTo(Partido::class, 'id_partido');
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

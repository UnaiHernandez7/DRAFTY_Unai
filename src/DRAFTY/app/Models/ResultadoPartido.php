<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResultadoPartido extends Model
{
    protected $table = 'resultados_partido';
    protected $primaryKey = 'id_resultado';

    protected $fillable = [
        'id_partido',
        'goles_local',
        'goles_visitante',
        'registrado_por',
        'tipo_registro',
        'confirmado_local',
        'confirmado_visitante',
        'estado_resultado',
        'fecha_limite_resultado'
    ];

    protected $casts = [
        'confirmado_local' => 'boolean',
        'confirmado_visitante' => 'boolean',
        'fecha_limite_resultado' => 'datetime'
    ];

    public function partido()
    {
        return $this->belongsTo(Partido::class, 'id_partido');
    }

    public function registrador()
    {
        return $this->belongsTo(Usuario::class, 'registrado_por');
    }
}

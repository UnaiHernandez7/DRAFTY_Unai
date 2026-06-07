<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa resultadopartido dentro de la base de datos.
 */
class ResultadoPartido extends Model
{
    /**
     * Nombre de la tabla asociada al modelo.
     */
    protected $table = 'resultados_partido';
    /**
     * Clave primaria usada por el modelo.
     */
    protected $primaryKey = 'id_resultado';

    /**
     * Campos que se pueden rellenar de forma masiva.
     */
    protected $fillable = [
        'id_partido',
        'goles_local',
        'goles_visitante',
        'goles_local_local',
        'goles_visitante_local',
        'goles_local_visitante',
        'goles_visitante_visitante',
        'registrado_por',
        'tipo_registro',
        'confirmado_local',
        'confirmado_visitante',
        'estado_resultado',
        'fecha_limite_resultado'
    ];

    /**
     * Conversiones de tipo aplicadas a campos del modelo.
     */
    protected $casts = [
        'confirmado_local' => 'boolean',
        'confirmado_visitante' => 'boolean',
        'fecha_limite_resultado' => 'datetime'
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
    public function registrador()
    {
        return $this->belongsTo(Usuario::class, 'registrado_por');
    }
}

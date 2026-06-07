<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa estadisticatorneousuario dentro de la base de datos.
 */
class EstadisticaTorneoUsuario extends Model
{
    /**
     * Nombre de la tabla asociada al modelo.
     */
    protected $table = 'estadisticas_torneo_usuario';
    /**
     * Clave primaria usada por el modelo.
     */
    protected $primaryKey = 'id_estadistica_torneo_usuario';

    /**
     * Campos que se pueden rellenar de forma masiva.
     */
    protected $fillable = [
        'id_torneo',
        'id_usuario',
        'id_equipo',
        'goles',
        'asistencias',
        'porterias_cero',
        'partidos_jugados'
    ];

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

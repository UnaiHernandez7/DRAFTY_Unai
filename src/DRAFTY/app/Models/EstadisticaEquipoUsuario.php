<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa estadisticaequipousuario dentro de la base de datos.
 */
class EstadisticaEquipoUsuario extends Model
{
    /**
     * Nombre de la tabla asociada al modelo.
     */
    protected $table = 'estadisticas_equipo_usuario';
    /**
     * Clave primaria usada por el modelo.
     */
    protected $primaryKey = 'id_estadistica_equipo_usuario';

    /**
     * Campos que se pueden rellenar de forma masiva.
     */
    protected $fillable = [
        'id_equipo',
        'id_usuario',
        'partidos_jugados',
        'goles',
        'asistencias',
        'porterias_cero'
    ];

    /**
     * Gestiona informacion relacionada con equipos.
     */
    public function equipo()
    {
        return $this->belongsTo(Equipo::class, 'id_equipo');
    }

    /**
     * Gestiona informacion de usuarios.
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}

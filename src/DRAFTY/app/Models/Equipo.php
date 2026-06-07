<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa equipo dentro de la base de datos.
 */
class Equipo extends Model
{
    /**
     * Nombre de la tabla asociada al modelo.
     */
    protected $table = "equipos";
    /**
     * Clave primaria usada por el modelo.
     */
    protected $primaryKey = "id_equipo";

    /**
     * Indica si el modelo usa marcas de tiempo automaticas.
     */
    public $timestamps = false;

    /**
     * Campos que se pueden rellenar de forma masiva.
     */
    protected $fillable = [
        'nombre_equipo',
        'descripcion',
        'privacidad',
        'fecha_creacion',
        'id_creador'
    ];

    /**
     * Relacion con el usuario creador del registro.
     */
    public function creador()
    {
        return $this->belongsTo(Usuario::class, 'id_creador');
    }

    /**
     * Relacion con los usuarios asociados.
     */
    public function usuarios()
    {
        return $this->belongsToMany(Usuario::class, 'equipo_usuarios', 'id_equipo', 'id_usuario')
            ->withPivot('rol_en_equipo', 'estado');
    }

    /**
     * Relacion con los torneos asociados.
     */
    public function torneos()
    {
        return $this->belongsToMany(Torneo::class, 'torneo_equipos', 'id_equipo', 'id_torneo');
    }

    /**
     * Gestiona informacion relacionada con partidos.
     */
    public function partidosLocal()
    {
        return $this->hasMany(Partido::class, 'id_equipo_local');
    }

    /**
     * Gestiona informacion relacionada con partidos.
     */
    public function partidosVisitante()
    {
        return $this->hasMany(Partido::class, 'id_equipo_visitante');
    }

    /**
     * Gestiona informacion de usuarios.
     */
    public function estadisticasUsuarios()
    {
        return $this->hasMany(EstadisticaEquipoUsuario::class, 'id_equipo');
    }

    /**
     * Gestiona mensajes del chat.
     */
    public function mensajes()
    {
        return $this->hasMany(MensajeEquipo::class, 'id_equipo');
    }
}

<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa torneo dentro de la base de datos.
 */
class Torneo extends Model
{
    /**
     * Nombre de la tabla asociada al modelo.
     */
    protected $table = "torneos";
    /**
     * Clave primaria usada por el modelo.
     */
    protected $primaryKey = "id_torneo";

    /**
     * Indica si el modelo usa marcas de tiempo automaticas.
     */
    public $timestamps = false;

    /**
     * Campos que se pueden rellenar de forma masiva.
     */
    protected $fillable = [
        'id_organizador',
        'nombre_torneo',
        'descripcion',
        'tipo_torneo',
        'tipo_futbol',
        'max_equipos',
        'privacidad',
        'codigo_acceso',
        'estado_torneo',
        'fecha_inicio',
        'fecha_fin',
        'cuota_inscripcion',
        'premio',
        'nombre_lugar',
        'direccion',
        'ciudad',
        'provincia',
        'latitud',
        'longitud',
        'estado'
    ];

    /**
     * Relacion con los equipos asociados.
     */
    public function equipos()
    {
        return $this->belongsToMany(Equipo::class, 'torneo_equipos', 'id_torneo', 'id_equipo')
            ->withPivot('estado_inscripcion', 'fecha_inscripcion', 'posicion_final');
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function organizador()
    {
        return $this->belongsTo(Usuario::class, 'id_organizador');
    }

    /**
     * Gestiona informacion relacionada con partidos.
     */
    public function partidosBracket()
    {
        return $this->hasMany(TorneoPartido::class, 'id_torneo');
    }

    /**
     * Gestiona informacion de usuarios.
     */
    public function estadisticasUsuarios()
    {
        return $this->hasMany(EstadisticaTorneoUsuario::class, 'id_torneo');
    }

    /**
     * Gestiona goles registrados.
     */
    public function goles()
    {
        return $this->hasMany(GolTorneoPartido::class, 'id_torneo');
    }
}

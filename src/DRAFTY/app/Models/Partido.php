<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Modelo que representa partido dentro de la base de datos.
 */
class Partido extends Model
{
    use HasFactory;
    /**
     * Nombre de la tabla asociada al modelo.
     */
    protected $table = "partidos";
    /**
     * Clave primaria usada por el modelo.
     */
    protected $primaryKey = "id_partido";

    /**
     * Indica si el modelo usa marcas de tiempo automaticas.
     */
    public $timestamps = false;

    /**
     * Campos que se pueden rellenar de forma masiva.
     */
    protected $fillable = [
        'titulo',
        'fecha',
        'hora',
        'tipo_futbol',
        'nivel',
        'descripcion',
        'estado',
        'jugadores_minimos',
        'fecha_limite_resultado',
        'id_arbitro',
        'es_competitivo',
        'estadisticas_actualizadas',
        'es_publico',
        'codigo_acceso',
        'plazas_totales',
        'id_creador',
        'id_campo',
        'id_equipo_local',
        'id_equipo_visitante',
        'formacion_local',
        'formacion_visitante',
        'goles_equipo_a',
        'goles_equipo_b',
        'goleadores'
    ];

    /**
     * Relacion con el usuario creador del registro.
     */
    public function creador()
    {
        return $this->belongsTo(Usuario::class, 'id_creador');
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function campo()
    {
        return $this->belongsTo(Campo::class, 'id_campo');
    }

    /**
     * Relacion con los equipos asociados.
     */
    public function equipoLocal()
    {
        return $this->belongsTo(Equipo::class, 'id_equipo_local');
    }

    /**
     * Gestiona informacion relacionada con equipos.
     */
    public function equipoVisitante()
    {
        return $this->belongsTo(Equipo::class, 'id_equipo_visitante');
    }

    /**
     * Relacion con los usuarios asociados.
     */
    public function usuarios()
    {
        return $this->belongsToMany(Usuario::class, 'participantes_partido', 'id_partido', 'id_usuario')
            ->withPivot('estado_participacion', 'equipo_asignado', 'posicion_asignada', 'es_capitan');
    }

    /**
     * Gestiona informacion de usuarios.
     */
    public function usuariosConfirmados()
    {
        return $this->belongsToMany(Usuario::class, 'participantes_partido', 'id_partido', 'id_usuario')
            ->wherePivot('estado_participacion', 'confirmado')
            ->withPivot('estado_participacion', 'equipo_asignado', 'posicion_asignada', 'es_capitan');
    }

    /**
     * Gestiona mensajes del chat.
     */
    public function mensajes()
    {
        return $this->hasMany(MensajePartido::class, 'id_partido');
    }

    /**
     * Gestiona goles registrados.
     */
    public function goles()
    {
        return $this->hasMany(GolPartido::class, 'id_partido');
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function resultado()
    {
        return $this->hasOne(ResultadoPartido::class, 'id_partido');
    }

    /**
     * Gestiona votos de MVP.
     */
    public function votosMvp()
    {
        return $this->hasMany(VotoMvp::class, 'id_partido');
    }

    /**
     * Gestiona valoraciones entre jugadores.
     */
    public function valoraciones()
    {
        return $this->hasMany(ValoracionJugador::class, 'id_partido');
    }
}

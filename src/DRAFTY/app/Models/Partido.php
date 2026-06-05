<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Partido extends Model
{
    use HasFactory;
    protected $table = "partidos";
    protected $primaryKey = "id_partido";

    public $timestamps = false;

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

    // Partido lo crea un usuario
    public function creador()
    {
        return $this->belongsTo(Usuario::class, 'id_creador');
    }

    // Partido pertenece a un campo
    public function campo()
    {
        return $this->belongsTo(Campo::class, 'id_campo');
    }

    // Equipos del partido
    public function equipoLocal()
    {
        return $this->belongsTo(Equipo::class, 'id_equipo_local');
    }

    public function equipoVisitante()
    {
        return $this->belongsTo(Equipo::class, 'id_equipo_visitante');
    }

    // Jugadores del partido
    public function usuarios()
    {
        return $this->belongsToMany(Usuario::class, 'participantes_partido', 'id_partido', 'id_usuario')
            ->withPivot('estado_participacion', 'visto_por_invitado', 'equipo_asignado', 'posicion_asignada', 'es_capitan');
    }

    public function usuariosConfirmados()
    {
        return $this->belongsToMany(Usuario::class, 'participantes_partido', 'id_partido', 'id_usuario')
            ->wherePivot('estado_participacion', 'confirmado')
            ->withPivot('estado_participacion', 'visto_por_invitado', 'equipo_asignado', 'posicion_asignada', 'es_capitan');
    }

    public function mensajes()
    {
        return $this->hasMany(MensajePartido::class, 'id_partido');
    }

    public function goles()
    {
        return $this->hasMany(GolPartido::class, 'id_partido');
    }

    public function resultado()
    {
        return $this->hasOne(ResultadoPartido::class, 'id_partido');
    }

    public function votosMvp()
    {
        return $this->hasMany(VotoMvp::class, 'id_partido');
    }

    public function valoraciones()
    {
        return $this->hasMany(ValoracionJugador::class, 'id_partido');
    }
}

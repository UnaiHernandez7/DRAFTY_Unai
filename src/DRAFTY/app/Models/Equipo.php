<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Equipo extends Model
{
    protected $table = "equipos";
    protected $primaryKey = "id_equipo";

    public $timestamps = false;

    protected $fillable = [
        'nombre_equipo',
        'descripcion',
        'privacidad',
        'fecha_creacion',
        'id_creador'
    ];

    // Equipo pertenece a un usuario (creador)
    public function creador()
    {
        return $this->belongsTo(Usuario::class, 'id_creador');
    }

    // Equipo tiene muchos usuarios
    public function usuarios()
    {
        return $this->belongsToMany(Usuario::class, 'equipo_usuarios', 'id_equipo', 'id_usuario')
            ->withPivot('rol_en_equipo', 'estado', 'visto_por_invitado');
    }

    // Equipo participa en torneos
    public function torneos()
    {
        return $this->belongsToMany(Torneo::class, 'torneo_equipos', 'id_equipo', 'id_torneo');
    }

    public function partidosLocal()
    {
        return $this->hasMany(Partido::class, 'id_equipo_local');
    }

    public function partidosVisitante()
    {
        return $this->hasMany(Partido::class, 'id_equipo_visitante');
    }

    public function estadisticasUsuarios()
    {
        return $this->hasMany(EstadisticaEquipoUsuario::class, 'id_equipo');
    }

    public function mensajes()
    {
        return $this->hasMany(MensajeEquipo::class, 'id_equipo');
    }
}

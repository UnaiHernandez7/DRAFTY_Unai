<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Torneo extends Model
{
    protected $table = "torneos";
    protected $primaryKey = "id_torneo";

    public $timestamps = false;

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

    // Torneo tiene muchos equipos
    public function equipos()
    {
        return $this->belongsToMany(Equipo::class, 'torneo_equipos', 'id_torneo', 'id_equipo')
            ->withPivot('estado_inscripcion', 'fecha_inscripcion', 'posicion_final');
    }

    public function organizador()
    {
        return $this->belongsTo(Usuario::class, 'id_organizador');
    }

    public function partidosBracket()
    {
        return $this->hasMany(TorneoPartido::class, 'id_torneo');
    }

    public function estadisticasUsuarios()
    {
        return $this->hasMany(EstadisticaTorneoUsuario::class, 'id_torneo');
    }

    public function goles()
    {
        return $this->hasMany(GolTorneoPartido::class, 'id_torneo');
    }
}

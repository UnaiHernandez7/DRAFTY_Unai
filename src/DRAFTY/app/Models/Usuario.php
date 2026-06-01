<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Model
{
    use HasApiTokens;

    protected $table = "usuarios";
    protected $primaryKey = "id_usuario";

    public $timestamps = false;

    protected $fillable = [
        'nombre_usuario',
        'nombre',
        'apellido',
        'email',
        'contrasena',
        'fecha_registro',
        'foto_perfil',
        'ciudad',
        'posiciones_favoritas',
        'rol'
    ];

    protected $hidden = [
        'contrasena'
    ];

    // Usuario tiene muchos equipos
    public function equipos()
    {
        return $this->belongsToMany(Equipo::class, 'equipo_usuarios', 'id_usuario', 'id_equipo')
            ->withPivot('rol_en_equipo', 'estado');
    }

    // Usuario participa en muchos partidos
    public function partidos()
    {
        return $this->belongsToMany(Partido::class, 'participantes_partido', 'id_usuario', 'id_partido')
            ->withPivot('estado_participacion', 'equipo_asignado', 'posicion_asignada', 'es_capitan');
    }

    // Usuario tiene una estadistica
    public function estadisticas()
    {
        return $this->hasOne(Estadistica::class, 'id_usuario');
    }

    // Usuario tiene un perfil competitivo
    public function competitivo()
    {
        return $this->hasOne(Competitivo::class, 'id_usuario');
    }

    public function solicitudesEnviadas()
    {
        return $this->hasMany(Amistad::class, 'id_usuario_emisor');
    }

    public function solicitudesRecibidas()
    {
        return $this->hasMany(Amistad::class, 'id_usuario_receptor');
    }

    public function amigos()
    {
        $ids = Amistad::where('estado', 'aceptada')
            ->where(function ($query) {
                $query->where('id_usuario_emisor', $this->id_usuario)
                    ->orWhere('id_usuario_receptor', $this->id_usuario);
            })
            ->get()
            ->map(fn ($amistad) => $amistad->id_usuario_emisor === $this->id_usuario
                ? $amistad->id_usuario_receptor
                : $amistad->id_usuario_emisor
            );

        return Usuario::whereIn('id_usuario', $ids);
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'id_usuario');
    }

    public function estadisticasEquipo()
    {
        return $this->hasMany(EstadisticaEquipoUsuario::class, 'id_usuario');
    }

    public function mensajesEquipo()
    {
        return $this->hasMany(MensajeEquipo::class, 'id_usuario');
    }

    public function valoracionesRecibidas()
    {
        return $this->hasMany(ValoracionJugador::class, 'id_usuario_valorado');
    }

    public function votosMvpRecibidos()
    {
        return $this->hasMany(VotoMvp::class, 'id_usuario_votado');
    }
}

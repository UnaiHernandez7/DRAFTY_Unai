<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

/**
 * Modelo que representa usuario dentro de la base de datos.
 */
class Usuario extends Model
{
    use HasApiTokens;

    /**
     * Nombre de la tabla asociada al modelo.
     */
    protected $table = "usuarios";
    /**
     * Clave primaria usada por el modelo.
     */
    protected $primaryKey = "id_usuario";

    /**
     * Indica si el modelo usa marcas de tiempo automaticas.
     */
    public $timestamps = false;

    /**
     * Campos que se pueden rellenar de forma masiva.
     */
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

    /**
     * Campos ocultos cuando el modelo se convierte a respuesta.
     */
    protected $hidden = [
        'contrasena'
    ];

    /**
     * Relacion con los equipos asociados.
     */
    public function equipos()
    {
        return $this->belongsToMany(Equipo::class, 'equipo_usuarios', 'id_usuario', 'id_equipo')
            ->withPivot('rol_en_equipo', 'estado');
    }

    /**
     * Relacion con los partidos asociados.
     */
    public function partidos()
    {
        return $this->belongsToMany(Partido::class, 'participantes_partido', 'id_usuario', 'id_partido')
            ->withPivot('estado_participacion', 'equipo_asignado', 'posicion_asignada', 'es_capitan');
    }

    /**
     * Relacion con las estadisticas asociadas.
     */
    public function estadisticas()
    {
        return $this->hasOne(Estadistica::class, 'id_usuario');
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function competitivo()
    {
        return $this->hasOne(Competitivo::class, 'id_usuario');
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function solicitudesEnviadas()
    {
        return $this->hasMany(Amistad::class, 'id_usuario_emisor');
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function solicitudesRecibidas()
    {
        return $this->hasMany(Amistad::class, 'id_usuario_receptor');
    }

    /**
     * Gestiona datos relacionados con amigos y solicitudes.
     */
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

    /**
     * Gestiona datos de pagos.
     */
    public function pagos()
    {
        return $this->hasMany(Pago::class, 'id_usuario');
    }

    /**
     * Gestiona informacion relacionada con equipos.
     */
    public function estadisticasEquipo()
    {
        return $this->hasMany(EstadisticaEquipoUsuario::class, 'id_usuario');
    }

    /**
     * Gestiona informacion relacionada con equipos.
     */
    public function mensajesEquipo()
    {
        return $this->hasMany(MensajeEquipo::class, 'id_usuario');
    }

    /**
     * Gestiona valoraciones entre jugadores.
     */
    public function valoracionesRecibidas()
    {
        return $this->hasMany(ValoracionJugador::class, 'id_usuario_valorado');
    }

    /**
     * Gestiona votos de MVP.
     */
    public function votosMvpRecibidos()
    {
        return $this->hasMany(VotoMvp::class, 'id_usuario_votado');
    }
}

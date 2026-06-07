<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa competitivo dentro de la base de datos.
 */
class Competitivo extends Model
{
    /**
     * Nombre de la tabla asociada al modelo.
     */
    protected $table = "competitivo";
    /**
     * Clave primaria usada por el modelo.
     */
    protected $primaryKey = "id_competitivo";

    /**
     * Indica si el modelo usa marcas de tiempo automaticas.
     */
    public $timestamps = false;

    /**
     * Campos que se pueden rellenar de forma masiva.
     */
    protected $fillable = [
        'id_usuario',
        'rango',
        'puntos_competitivos',
        'partidos_competitivos_jugados',
        'partidos_competitivos_ganados',
        'partidos_competitivos_perdidos',
        'goles_competitivo',
        'asistencias_competitivo',
        'porterias_cero_competitivo',
        'tarjetas_amarillas_competitivo',
        'tarjetas_rojas_competitivo',
        'racha_actual',
        'activo',
        'precio_mensual',
        'fecha_inicio_suscripcion',
        'fecha_fin_suscripcion',
        'estado_pago',
        'fecha_actualizacion'
    ];

    /**
     * Relacion con los usuarios asociados.
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}
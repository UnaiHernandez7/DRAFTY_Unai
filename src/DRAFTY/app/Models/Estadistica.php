<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa estadistica dentro de la base de datos.
 */
class Estadistica extends Model
{
    /**
     * Nombre de la tabla asociada al modelo.
     */
    protected $table = "estadisticas";
    /**
     * Clave primaria usada por el modelo.
     */
    protected $primaryKey = "id_estadistica";

    /**
     * Indica si el modelo usa marcas de tiempo automaticas.
     */
    public $timestamps = false;

    /**
     * Campos que se pueden rellenar de forma masiva.
     */
    protected $fillable = [
        'id_usuario',
        'partidos_jugados',
        'partidos_ganados',
        'partidos_perdidos',
        'goles',
        'asistencias',
        'porterias_cero',
        'tarjetas_amarillas',
        'tarjetas_rojas'
    ];

    /**
     * Relacion con los usuarios asociados.
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}

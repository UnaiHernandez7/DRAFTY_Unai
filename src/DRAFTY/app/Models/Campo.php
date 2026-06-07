<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Modelo que representa campo dentro de la base de datos.
 */
class Campo extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada al modelo.
     */
    protected $table = "campos";
    /**
     * Clave primaria usada por el modelo.
     */
    protected $primaryKey = "id_campo";

    /**
     * Indica si el modelo usa marcas de tiempo automaticas.
     */
    public $timestamps = false;

    /**
     * Campos que se pueden rellenar de forma masiva.
     */
    protected $fillable = [
        'nombre_campo',
        'direccion',
        'ciudad',
        'provincia',
        'codigo_postal',
        'latitud',
        'longitud',
        'tipo_campo',
        'precio_hora'
    ];

    /**
     * Obtiene los partidos asociados al campo.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function partidos()
    {
        return $this->hasMany(Partido::class, 'id_campo');
    }
}

<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Campo extends Model
{
    protected $table = "campos";
    protected $primaryKey = "id_campo";

    public $timestamps = false;

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

    // Campo tiene muchos partidos
    public function partidos()
    {
        return $this->hasMany(Partido::class, 'id_campo');
    }
}

<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Estadistica extends Model
{
    protected $table = "estadisticas";
    protected $primaryKey = "id_estadistica";

    public $timestamps = false;

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

    // Estadistica pertenece a un usuario
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}

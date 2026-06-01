<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Competitivo extends Model
{
    protected $table = "competitivo";
    protected $primaryKey = "id_competitivo";

    public $timestamps = false;

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

    // Competitivo pertenece a un usuario
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}
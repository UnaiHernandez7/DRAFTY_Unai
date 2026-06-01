<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ValoracionJugador extends Model
{
    protected $table = 'valoraciones_jugador';
    protected $primaryKey = 'id_valoracion';

    protected $fillable = [
        'id_partido',
        'id_usuario_valorado',
        'id_usuario_valorador',
        'puntuacion',
        'comentario'
    ];

    public function partido()
    {
        return $this->belongsTo(Partido::class, 'id_partido');
    }

    public function valorado()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_valorado');
    }

    public function valorador()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_valorador');
    }
}

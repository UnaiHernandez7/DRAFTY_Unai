<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VotoMvp extends Model
{
    protected $table = 'votos_mvp';
    protected $primaryKey = 'id_voto';

    protected $fillable = [
        'id_partido',
        'id_usuario_votante',
        'id_usuario_votado',
        'peso_voto'
    ];

    public function partido()
    {
        return $this->belongsTo(Partido::class, 'id_partido');
    }

    public function votante()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_votante');
    }

    public function votado()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_votado');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MensajePartido extends Model
{
    protected $table = 'mensajes_partido';
    protected $primaryKey = 'id_mensaje';

    public $timestamps = false;

    protected $fillable = [
        'id_partido',
        'id_usuario',
        'mensaje',
        'fecha_envio'
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}

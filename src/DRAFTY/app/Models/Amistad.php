<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Amistad extends Model
{
    protected $table = 'amistades';
    protected $primaryKey = 'id_amistad';

    public $timestamps = false;

    protected $fillable = [
        'id_usuario_emisor',
        'id_usuario_receptor',
        'estado',
        'visto_por_receptor',
        'fecha_solicitud',
        'fecha_respuesta'
    ];

    public function emisor()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_emisor');
    }

    public function receptor()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_receptor');
    }
}

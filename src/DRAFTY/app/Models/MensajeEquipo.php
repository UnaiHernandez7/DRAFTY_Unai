<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MensajeEquipo extends Model
{
    protected $table = 'mensajes_equipo';
    protected $primaryKey = 'id_mensaje';

    protected $fillable = [
        'id_equipo',
        'id_usuario',
        'mensaje'
    ];

    public function equipo()
    {
        return $this->belongsTo(Equipo::class, 'id_equipo');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa pago dentro de la base de datos.
 */
class Pago extends Model
{
    /**
     * Nombre de la tabla asociada al modelo.
     */
    protected $table = 'pagos';
    /**
     * Clave primaria usada por el modelo.
     */
    protected $primaryKey = 'id_pago';

    /**
     * Campos que se pueden rellenar de forma masiva.
     */
    protected $fillable = [
        'id_usuario',
        'tipo_pago',
        'importe',
        'estado_pago',
        'fecha_pago'
    ];

    /**
     * Gestiona informacion de usuarios.
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}

<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Cita extends Model
{
    protected $fillable = [
        'alumno_id',
        'docente_id',
        'fecha_cita',
        'modalidad',
        'motivo',
        'estado',
        'observaciones', // nuevo campo
    ];

    protected $casts = [
        'fecha_cita' => 'datetime:c',
        // 'observaciones' no requiere cast especial (string / null)
    ];

    // MUY IMPORTANTE: la llave del User en Mongo es "_id"
    public function alumno()
    {
        return $this->belongsTo(User::class, 'alumno_id', '_id');
    }

    public function docente()
    {
        return $this->belongsTo(User::class, 'docente_id', '_id');
    }
}

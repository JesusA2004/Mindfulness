<?php

namespace App\Models;

use App\Models\User;   
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Eloquent\Casts\ObjectId;

class Cita extends Model
{
    protected $fillable = [
        'alumno_id',
        'docente_id',
        'fecha_cita',
        'modalidad',
        'motivo',
        'estado',
        'observaciones', 
    ];

    protected $casts = [
        'fecha_cita' => 'datetime:c',
        'alumno_id'  => ObjectId::class,
        'docente_id' => ObjectId::class,
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

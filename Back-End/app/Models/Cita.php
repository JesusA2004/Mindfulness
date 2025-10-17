<?php

namespace App\Models;

use MongoDB\BSON\ObjectId;
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Eloquent\Casts\ObjectId as ObjectIdCast;

class Cita extends Model
{
    protected $fillable = ['alumno_id','docente_id','fecha_cita','modalidad','motivo','estado'];

    protected $casts = [
        'fecha_cita' => 'datetime:c'
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

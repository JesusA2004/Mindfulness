<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Cita extends Model
{
    
    protected $perPage = 20;

    protected $fillable = [
        'alumno_id',
        'docente_id',
        'fecha_cita',
        'modalidad',
        'motivo',
        'estado',
    ];

}

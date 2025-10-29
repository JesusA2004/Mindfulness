<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Actividad extends Model
{
    protected $collection = 'actividads';

    protected $perPage = 20;

    protected $fillable = [
        'fechaAsignacion',
        'fechaFinalizacion',
        'fechaMaxima',
        'nombre',
        'docente_id',
        'tecnica_id',
        'descripcion',
        'participantes',
    ];

    protected $casts = [
        'fechaAsignacion'   => 'string',
        'fechaFinalizacion' => 'string',
        'fechaMaxima'       => 'string',
        'participantes'     => 'array',
    ];
}

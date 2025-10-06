<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Encuesta extends Model
{
    protected $collection = 'encuestas';

    protected $fillable = [
        'titulo',
        'descripcion',
        'fechaAsignacion',
        'fechaFinalizacion',
        'duracion_estimada',
        'cuestionario',
    ];

    protected $casts = [
        'fechaAsignacion'   => 'date:Y-m-d',
        'fechaFinalizacion' => 'date:Y-m-d',
        'cuestionario'      => 'array',
    ];

    protected $perPage = 20;
}

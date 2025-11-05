<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Test extends Model
{
    protected $collection = 'tests';

    protected $fillable = [
        'nombre',
        'descripcion',
        'duracion_estimada',
        'fechaAplicacion',
        'cuestionario',
    ];

    protected $casts = [
        'fechaAplicacion' => 'date:Y-m-d',
        'cuestionario'    => 'array',
        'duracion_estimada' => 'integer',
    ];

    protected $perPage = 20;
    
}

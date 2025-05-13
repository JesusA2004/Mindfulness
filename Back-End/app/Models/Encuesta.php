<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Encuesta extends Model
{
    
    protected $perPage = 20;

    protected $fillable = [
        'titulo',
        'descripcion',
        'fechaAsignacion',
        'fechaFinalizacion',
        'duracion_estimada',
    ];

}

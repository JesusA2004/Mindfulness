<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Tecnica extends Model
{
    protected $collection = 'tecnicas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'dificultad',
        'duracion',
        'categoria',
        'calificaciones',
        'recursos',
    ];

    protected $casts = [
        'duracion'       => 'integer',
        'calificaciones' => 'array',
        'recursos'       => 'array',
    ];
    
}

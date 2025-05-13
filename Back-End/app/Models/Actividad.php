<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Actividad extends Model
{
    
    protected $perPage = 20;

    protected $fillable = [
        'fechaAsignacion',
        'fechaFinalizacion',
        'fechaMaxima',
        'nombre',
        'docente_id',
        'tecnica_id',
        'descripcion',
    ];

}

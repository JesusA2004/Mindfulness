<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Test extends Model
{
    
    protected $perPage = 20;

    protected $fillable = [
        'nombre',
        'descripcion',
        'duracion_estimada',
        'fechaAplicacion',
        // 'cuestionario' lo manejas como push()/set
    ];

}

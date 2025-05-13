<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Recompensa extends Model
{
    
    protected $perPage = 20;

    protected $fillable = [
        'nombre',
        'descripcion',
        'puntos_necesarios',
        'stock',
    ];

}

<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Tecnica extends Model
{
    
    protected $perPage = 20;

    protected $fillable = [
        'nombre',
        'descripcion',
        'dificultad',
        'duracion',
        'categoria',
        // para push() de subdocumentos no hace falta incluir 'calificaciones' ni 'recursos'
    ];

}

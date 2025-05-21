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

    // En App\Models\Tecnica.php
    public function calificaciones()
    {
        return $this->hasMany(Calificacion::class);
    }

    public function recursos()
    {
        return $this->hasMany(Recurso::class);
    }

}

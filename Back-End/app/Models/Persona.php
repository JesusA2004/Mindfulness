<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Persona extends Model
{
    
    protected $perPage = 20;

    protected $fillable = [
        'nombre',
        'apellidoPaterno',
        'apellidoMaterno',
        'fechaNacimiento',
        'telefono',
        'sexo',
        'carrera',
        'matricula',
        'cuatrimestre',
        'grupo',
    ];

}

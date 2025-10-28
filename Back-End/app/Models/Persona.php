<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Persona extends Model
{
    protected $collection = 'personas';
    protected $perPage = 20;

    /**
     * Campo unificado "cohorte":
     *  - Estudiante/Admin: string "CARRERA CUAT GRUPO" (p.ej. "ITI 10 A")
     *  - Profesor: array de strings (p.ej. ["ITI 3 A", "IA 5 B"])
     *
     * NOTA: Campos antiguos 'carrera', 'cuatrimestre', 'grupo' ya no se usan.
     */
    protected $fillable = [
        'nombre',
        'apellidoPaterno',
        'apellidoMaterno',
        'fechaNacimiento',
        'telefono',
        'sexo',
        'matricula',
        'cohorte',
    ];

    // Si prefieres recibir string para estudiante y array para profesor
    // NO castees; lo dejamos sin casts para mantener el tipo entrante.
    protected $casts = [
        'fechaNacimiento' => 'date:Y-m-d',
    ];
}

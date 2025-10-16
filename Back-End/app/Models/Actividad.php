<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Actividad extends Model
{
    // Si ya configuraste la conexión por default a Mongo, no necesitas esta línea.
    // protected $connection = 'mongodb';

    // Asegura el nombre de la colección si no coincide con la convención.
    protected $collection = 'actividads';

    protected $perPage = 20;

    protected $fillable = [
        'fechaAsignacion',
        'fechaFinalizacion',
        'fechaMaxima',
        'nombre',
        'docente_id',
        'tecnica_id',
        'descripcion',
        'participantes', // 👈 IMPORTANTE: para que se guarde
    ];

    protected $casts = [
        // Recibes 'YYYY-MM-DD' desde el front; mantenlas como string
        'fechaAsignacion'   => 'string',
        'fechaFinalizacion' => 'string',
        'fechaMaxima'       => 'string',

        // Guardar/leer el arreglo tal cual
        'participantes'     => 'array',
    ];
}

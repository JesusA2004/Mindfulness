<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Bitacora extends Model
{
    
    protected $perPage = 20;

    protected $fillable = [
        'titulo',
        'descripcion',
        'fecha',
        'alumno_id',
    ];

    protected $casts = [
        'fecha' => 'string', // 👈 importante
    ];

}

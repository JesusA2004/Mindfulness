<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Institucion extends Model
{
    // Conexión Mongo y nombre EXACTO de la colección creada por tu migración
    protected $connection = 'mongodb';
    protected $collection = 'institucions';

    // Si usas paginación en listados
    protected $perPage = 20;

    // ÚNICAMENTE los campos que existen en la colección
    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'email',
    ];

    // Relaciones
    public function users()
    {
        return $this->hasMany(User::class, 'institucion_id', '_id');
    }
}

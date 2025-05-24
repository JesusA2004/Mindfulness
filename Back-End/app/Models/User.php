<?php

namespace App\Models;

use MongoDB\Laravel\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    
    protected $perPage = 20;

    protected $fillable = [
        'name',
        'matricula',
        'password',
        'email',
        'rol',
        'estatus',
        'urlFotoPerfil',
        'persona_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Obtiene el identificador único del usuario para el JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey(); // normalmente el _id en Mongo
    }

    /**
     * Retorna el array de las reclamaciones personalizadas que se incluirán en el JWT.
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

}

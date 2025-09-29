<?php

namespace App\Models;

use Exception;
use MongoDB\Laravel\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use MongoDB\Laravel\Eloquent\Casts\ObjectId;   // <-- IMPORTANTE
use App\Models\Institucion;
use App\Models\Persona;

class User extends Authenticatable implements JWTSubject
{
    protected $connection = 'mongodb';
    protected $collection  = 'users';

    protected $perPage = 20;

    protected $fillable = [
        'name',
        'matricula',
        'password',
        'email',
        'rol',            // estudiante | profesor | admin
        'estatus',        // activo | bajaSistema | bajaTemporal
        'urlFotoPerfil',
        'persona_id',
        'institucion_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // >>>> Usa el CAST correcto basado en clases
    protected $casts = [
        'persona_id'     => ObjectId::class,
        'institucion_id' => ObjectId::class,
    ];

    // Relaciones
    public function institucion()
    {
        return $this->belongsTo(Institucion::class, 'institucion_id', '_id');
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id', '_id');
    }

    // Hash automático del password si viene en texto plano
    public function setPasswordAttribute($value)
    {
        if (!empty($value) && substr($value, 0, 4) !== '$2y$') {
            $this->attributes['password'] = bcrypt($value);
        } else {
            $this->attributes['password'] = $value;
        }
    }

    // Enforce: deben existir persona e institución antes de crear
    protected static function booted()
    {
        static::creating(function (self $user) {
            if (empty($user->institucion_id)) {
                throw new Exception('institucion_id es requerido para crear un usuario.');
            }
            if (empty($user->persona_id)) {
                throw new Exception('persona_id es requerido para crear un usuario.');
            }

            if (!Institucion::where('_id', $user->institucion_id)->exists()) {
                throw new Exception('La institución referenciada no existe.');
            }
            if (!Persona::where('_id', $user->persona_id)->exists()) {
                throw new Exception('La persona referenciada no existe.');
            }
        });
    }

    // JWT
    public function getJWTIdentifier()
    {
        return $this->getKey(); // _id en Mongo
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}

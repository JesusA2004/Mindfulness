<?php

namespace App\Models;

use Exception;
use App\Models\Persona;
use App\Models\Institucion;
use MongoDB\Laravel\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use MongoDB\Laravel\Eloquent\Casts\ObjectId;
use MongoDB\Laravel\Eloquent\Casts\ObjectId as ObjectIdCast;

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
        'puntosCanjeo',
        'urlFotoPerfil',
        'persona_id',
        'current_jti'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // >>>> Usa el CAST correcto basado en clases
    protected $casts = [
        'persona_id'     => ObjectId::class,
        'puntosCanjeo'  => 'int',
    ];

    protected $attributes = [
        'puntosCanjeo' => 0,
    ];

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
            if (empty($user->persona_id)) {
                throw new Exception('persona_id es requerido para crear un usuario.');
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

    /** Incrementa puntos de forma segura */
    public function earnPoints(int $points, ?string $reason = null): self
    {
        if ($points <= 0) return $this;
        // $inc es atómico en Mongo
        static::where('_id', $this->_id)->update(['$inc' => ['puntosCanjeo' => $points]]);
        $this->refresh();
        // (Opcional) dispara evento/log si llevas bitácora
        return $this;
    }

    /** Canjea (resta) puntos sin permitir negativos */
    public function redeemPoints(int $points, ?string $reason = null): self
    {
        if ($points <= 0) return $this;

        // Lee el valor actual y aplica regla de no-negativo
        $current = (int) ($this->puntosCanjeo ?? 0);
        $toSubtract = min($points, $current);

        if ($toSubtract > 0) {
            static::where('_id', $this->_id)->update(['$inc' => ['puntosCanjeo' => -$toSubtract]]);
            $this->refresh();
        }
        return $this;
    }

}

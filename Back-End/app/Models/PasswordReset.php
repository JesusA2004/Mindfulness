<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class PasswordReset extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'password_resets';

    protected $fillable = [
        'email',        // string
        'token_hash',   // string (Hash::make del token plano)
        'expires_at',   // datetime string ISO
        'used_at',      // datetime string ISO|null
        'ip',           // string|null
        'ua',           // string|null
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    protected $attributes = [
        'used_at' => null,
    ];
}

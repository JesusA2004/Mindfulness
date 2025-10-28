<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Canales privados de broadcasting
|--------------------------------------------------------------------------
| Aquí defines quién puede escuchar cada canal privado o de presencia.
| Laravel usa este archivo para autorizar las suscripciones de Echo/Pusher.
|
| - Canal user.{id} → usado para recordatorios individuales (Bitácora, Citas, etc.)
| - Canal role.estudiante → usado para notificar a TODOS los alumnos (Recompensas nuevas)
*/

/**
 * Canal privado individual por usuario.
 * Permite que solo el propio usuario autenticado escuche su canal "user.{id}".
 */
Broadcast::channel('user.{id}', function ($user, $id) {
    // Soporta tanto Mongo (_id string) como SQL (id numérico)
    return (string)($user->id ?? $user->_id ?? '') === (string)$id;
});

/**
 * Canal privado por rol de estudiante.
 * Solo los usuarios con rol "estudiante" podrán suscribirse a "role.estudiante".
 * Esto se usa para enviar notificaciones globales (ej. nueva recompensa disponible).
 */
Broadcast::channel('role.estudiante', function ($user) {
    return isset($user->rol) && strtolower($user->rol) === 'estudiante';
});

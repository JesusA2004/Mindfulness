<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{id}', function ($user, $id) {
    // Soporta Mongo (_id string) y SQL (id numérico)
    return (string)($user->id ?? $user->_id) === (string)$id;
});

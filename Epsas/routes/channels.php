<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('lecturas', function ($user): bool {
    return $user->hasAnyRole(['administrador', 'secretaria', 'tecnico']);
});

<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id; // only allow the user to join their own channel
});

Broadcast::channel('superadmins', function ($user) {
    return $user->hasRole('superadmin');
});
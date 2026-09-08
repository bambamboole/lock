<?php
declare(strict_types=1);

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Auth.Models.User.{id}', function ($user, $id) {
    return (string) $user->id === (string) $id;
});

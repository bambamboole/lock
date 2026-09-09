<?php
declare(strict_types=1);

use App\Auth\Models\User;

// Only the keys this app changes from the framework defaults live here.
return [

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', User::class),
        ],
    ],

];

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
        /*
         * Not the stock `eloquent` driver: identity is realm-local, so every
         * user lookup carries a realm constraint. See RealmUserProvider.
         */
        'users' => [
            'driver' => 'realm-eloquent',
            'model' => env('AUTH_MODEL', User::class),
        ],
    ],

];

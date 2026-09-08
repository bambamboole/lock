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

        /*
         * API requests authenticate through the `oidc` guard that laravel-oidc
         * registers itself (see routes/api.php). This passport guard is never
         * routed to, but it must stay: Passport resolves the user provider for
         * personal access tokens by looking for a guard with the `passport`
         * driver, so `User::createToken()` throws without it.
         */
        'api' => [
            'driver' => 'passport',
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

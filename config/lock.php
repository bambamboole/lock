<?php
declare(strict_types=1);

return [
    /*
     * The instance administrator `app:bootstrap` reconciles into the master
     * realm on every deploy. Every value is optional and unset means
     * unmanaged: without an address nothing is touched, and without a password
     * an existing administrator keeps the one they have.
     */
    'admin' => [
        'email' => env('LOCK_ADMIN_EMAIL'),
        'password' => env('LOCK_ADMIN_PASSWORD'),
        'name' => env('LOCK_ADMIN_NAME', 'Administrator'),
    ],
];

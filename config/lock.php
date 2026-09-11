<?php
declare(strict_types=1);

return [
    /*
     * Unset means unmanaged: without an address nothing is touched, and without
     * a password an existing administrator keeps theirs.
     */
    'admin' => [
        'email' => env('LOCK_ADMIN_EMAIL'),
        'password' => env('LOCK_ADMIN_PASSWORD'),
        'name' => env('LOCK_ADMIN_NAME', 'Administrator'),
    ],
];

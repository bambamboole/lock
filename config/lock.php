<?php
declare(strict_types=1);

return [

    'master_realm' => env('LOCK_MASTER_REALM', 'admin'),

    /*
     * The console's own client, which `app:bootstrap` reconciles into the
     * master realm on every deploy. Without both values it is left alone.
     */
    'console_client' => [
        'id' => env('LOCK_CONSOLE_CLIENT_ID'),
        'secret' => env('LOCK_CONSOLE_CLIENT_SECRET'),
    ],

    /*
     * Unset means unmanaged: without an address nothing is touched, and
     * without a password an existing administrator keeps theirs.
     */
    'admin' => [
        'email' => env('LOCK_ADMIN_EMAIL'),
        'password' => env('LOCK_ADMIN_PASSWORD'),
        'name' => env('LOCK_ADMIN_NAME', 'Administrator'),
    ],

];

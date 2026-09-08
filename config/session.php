<?php
declare(strict_types=1);

use Illuminate\Support\Str;

// Only the keys this app changes from the framework defaults live here.
return [

    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug((string) env('APP_NAME', 'laravel')).'-session',
    ),

    'serialization' => 'json',

];

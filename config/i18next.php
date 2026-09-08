<?php
declare(strict_types=1);

// Only the keys this app changes from the package defaults live here.
return [

    'save_missing' => [
        'enabled' => env('I18NEXT_SAVE_MISSING', false),
        'middleware' => [],
    ],

    'output' => 'nested',

    'namespaces' => true,

];

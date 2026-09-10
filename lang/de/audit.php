<?php
declare(strict_types=1);

return [
    'events' => [
        'model' => [
            'created' => 'Erstellt',
            'updated' => 'Aktualisiert',
            'deleted' => 'Gelöscht',
        ],
        'realm' => [
            'created' => 'Realm angelegt',
            'updated' => 'Realm aktualisiert',
            'deleted' => 'Realm gelöscht',
            'client' => [
                'created' => 'Client angelegt',
                'updated' => 'Client aktualisiert',
                'secret-rotated' => 'Client-Secret rotiert',
                'revoked' => 'Client gesperrt',
                'restored' => 'Client entsperrt',
                'deleted' => 'Client gelöscht',
            ],
        ],
        'admin' => [
            'roles' => [
                'updated' => 'Globale Rollen aktualisiert',
                'granted' => 'Super Admin vergeben',
            ],
            'password-reset' => ['sent' => 'Passwort-Reset gesendet'],
            'verification' => ['resent' => 'Bestätigungs-E-Mail erneut gesendet'],
        ],
    ],
];

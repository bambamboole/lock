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

<?php
declare(strict_types=1);

return [
    'events' => [
        'model' => [
            'created' => 'Erstellt',
            'updated' => 'Aktualisiert',
            'deleted' => 'Gelöscht',
        ],
        'realm' => ['updated' => 'Realm aktualisiert'],
        'invitation' => [
            'created' => 'Einladung gesendet',
            'cancelled' => 'Einladung storniert',
            'resent' => 'Einladung erneut gesendet',
        ],
        'member' => [
            'role-changed' => 'Mitgliederrolle geändert',
            'removed' => 'Mitglied entfernt',
        ],
        'role' => [
            'created' => 'Rolle erstellt',
            'updated' => 'Rolle aktualisiert',
            'deleted' => 'Rolle gelöscht',
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

<?php
declare(strict_types=1);

return [
    'categories' => [
        'auth' => 'Authentifizierung',
        'oauth' => 'OAuth',
        'admin' => 'Administration',
    ],
    'types' => [
        'auth' => [
            'login' => [
                'succeeded' => 'Anmeldung erfolgreich',
                'failed' => 'Anmeldung fehlgeschlagen',
            ],
            'logout' => 'Abgemeldet',
            'password' => ['reset' => 'Passwort zurückgesetzt'],
            'registration' => ['succeeded' => 'Registrierung abgeschlossen'],
            'mfa' => [
                'challenge-succeeded' => 'Zweiter Faktor akzeptiert',
                'challenge-failed' => 'Zweiter Faktor abgelehnt',
                'factor-enrollment-started' => 'Faktor-Einrichtung gestartet',
                'factor-confirmed' => 'Faktor bestätigt',
                'factor-revoked' => 'Faktor entfernt',
                'recovery-code-used' => 'Wiederherstellungscode verwendet',
            ],
        ],
        'oauth' => [
            'token' => [
                'issued' => 'Token ausgestellt',
                'failed' => 'Token-Anfrage fehlgeschlagen',
                'revoked' => 'Token widerrufen',
            ],
            'consent' => [
                'approved' => 'Zustimmung erteilt',
                'denied' => 'Zustimmung verweigert',
            ],
            'client-auth' => ['failed' => 'Client-Authentifizierung fehlgeschlagen'],
        ],
        'admin' => [
            'client' => [
                'provisioned' => 'Client bereitgestellt',
                'registered' => 'Client registriert',
            ],
            'keys' => ['rotated' => 'Signaturschlüssel rotiert'],
        ],
    ],
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
            'role' => [
                'created' => 'Realm-Rolle angelegt',
                'updated' => 'Realm-Rolle aktualisiert',
                'deleted' => 'Realm-Rolle gelöscht',
            ],
            'user' => [
                'roles-updated' => 'Realm-Rollen aktualisiert',
            ],
        ],
        'admin' => [
            'roles' => [
                'updated' => 'Globale Rollen aktualisiert',
                'granted' => 'Super Admin vergeben',
            ],
            'password-reset' => ['sent' => 'Passwort-Reset gesendet'],
            'verification' => ['resent' => 'Bestätigungs-E-Mail erneut gesendet'],
            'user' => [
                'created' => 'Benutzer angelegt',
                'updated' => 'Benutzer aktualisiert',
                'blocked' => 'Benutzer gesperrt',
                'unblocked' => 'Sperre aufgehoben',
                'deleted' => 'Benutzer gelöscht',
                'mfa-reset' => 'Mehrfaktor-Authentifizierung zurückgesetzt',
                'session-ended' => 'Sitzung beendet',
                'sessions-ended' => 'Alle Sitzungen beendet',
            ],
        ],
    ],
];

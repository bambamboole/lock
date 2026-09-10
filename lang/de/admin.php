<?php
declare(strict_types=1);

return [
    'permissions' => [
        'admin' => [
            'access' => 'Auf den Admin-Bereich zugreifen',
            'users' => [
                'manage' => 'Benutzer einsehen und verwalten',
            ],
            'realms' => [
                'view' => 'Realms ansehen',
                'manage' => 'Realms einsehen und verwalten',
            ],
            'roles' => [
                'manage' => 'Festlegen, welche Benutzer Admins sind',
            ],
        ],
    ],
    'activity' => [
        'heading' => 'Admin-Aktivität',
        'empty' => 'Noch keine Admin-Aktivität.',
        'system-actor' => 'System (Konsole)',
        'detail' => [
            'empty' => 'Keine weiteren Details erfasst.',
        ],
        'columns' => [
            'when' => 'Wann',
            'actor' => 'Akteur',
            'event' => 'Ereignis',
            'description' => 'Beschreibung',
        ],
    ],
    'users' => [
        'heading' => 'Benutzer',
        'columns' => [
            'member' => 'Mitglied',
            'realm' => 'Realm',
            'admin' => 'Admin',
            'created-at' => 'Beigetreten',
        ],
        'actions' => [
            'manage' => 'Verwalten',
        ],
        'detail' => [
            'summary' => [
                'heading' => 'Übersicht',
            ],
            'created-at' => 'Beigetreten',
            'realms' => [
                'heading' => 'Realm',
            ],
            'admin-roles' => [
                'heading' => 'Admin-Rollen',
            ],
            'account-actions' => [
                'heading' => 'Kontoaktionen',
            ],
            'roles' => [
                'field' => [
                    'label' => 'Globale Rollen',
                ],
            ],
            'update-roles' => 'Rollen aktualisieren',
            'roles-updated' => 'Globale Rollen aktualisiert.',
            'resend-verification' => 'Verifizierung erneut senden',
            'verification-sent' => 'Verifizierungs-E-Mail gesendet.',
            'send-password-reset' => 'Passwort-Reset senden',
            'password-reset-sent' => 'E-Mail zum Zurücksetzen des Passworts gesendet.',
        ],
        'already-verified' => 'Dieser Benutzer hat seine E-Mail-Adresse bereits verifiziert.',
    ],
    'realms' => [
        'heading' => 'Realms',
        'description' => 'Jeder Realm ist eine eigene Identitätsgrenze mit eigenen Benutzern, Clients und Authentifizierungseinstellungen.',
        'columns' => [
            'name' => 'Name',
            'slug' => 'Slug',
            'users' => 'Benutzer',
            'created-at' => 'Erstellt',
        ],
        'actions' => [
            'manage' => 'Verwalten',
        ],
        'created' => 'Realm angelegt.',
        'updated' => 'Realm aktualisiert.',
        'configuration-saved' => 'Realm-Konfiguration gespeichert.',
        'deleted' => 'Realm gelöscht.',
        'tabs' => [
            'general' => 'Allgemein',
            'configuration' => 'Konfiguration',
            'danger' => 'Gefahrenzone',
        ],
        'summary' => [
            'heading' => 'Übersicht',
            'slug' => 'Slug',
            'users' => 'Benutzer',
            'created-at' => 'Erstellt',
        ],
        'general' => [
            'heading' => 'Realm-Details',
            'subtitle' => 'Benennen Sie den Realm um. Die Kennung bleibt dauerhaft fest.',
            'submit' => 'Realm speichern',
        ],
        'danger' => [
            'heading' => 'Realm löschen',
            'subtitle' => 'Löscht den Realm samt Benutzern, Clients, Sitzungen und Tokens. Das lässt sich nicht rückgängig machen.',
            'confirm-name' => 'Realm-Namen bestätigen',
            'name-mismatch' => 'Der Realm-Name stimmt nicht überein.',
            'submit' => 'Realm löschen',
        ],
        'create' => [
            'heading' => 'Realm anlegen',
            'description' => 'Neue Realms starten mit den Standardeinstellungen für die Authentifizierung. Nach dem Anlegen können Sie diese konfigurieren.',
            'submit' => 'Realm anlegen',
        ],
        'sections' => [
            'general' => 'Allgemein',
            'tokens' => 'Token-Laufzeiten',
            'sessions' => 'Sitzungen',
            'mfa' => 'Mehrfaktor-Authentifizierung',
            'clients' => 'Client-Regeln',
        ],
        'factors' => [
            'totp' => 'Authenticator-App (TOTP)',
            'webauthn' => 'Sicherheitsschlüssel oder Passkey (WebAuthn)',
        ],
        'fields' => [
            'name' => [
                'label' => 'Realm-Name',
            ],
            'slug' => [
                'label' => 'Realm-Kennung',
                'help-text' => 'Wird in den Realm-URLs verwendet. Nur Kleinbuchstaben, Zahlen und einzelne Bindestriche. Später nicht mehr änderbar.',
            ],
            'access-token-lifetime' => [
                'label' => 'Laufzeit von Access-Tokens',
                'help-text' => 'Laufzeit neu ausgestellter Access-Tokens in Sekunden.',
            ],
            'id-token-lifetime' => [
                'label' => 'Laufzeit von ID-Tokens',
                'help-text' => 'Laufzeit neu ausgestellter ID-Tokens in Sekunden.',
            ],
            'client-credentials-lifetime' => [
                'label' => 'Laufzeit von Machine-to-Machine-Tokens',
                'help-text' => 'Laufzeit von Client-Credentials-Tokens in Sekunden.',
            ],
            'refresh-token-lifetime' => [
                'label' => 'Laufzeit von Refresh-Tokens',
                'help-text' => 'Laufzeit neu ausgestellter Refresh-Tokens in Sekunden.',
            ],
            'session-cookie-name' => [
                'label' => 'Name des Session-Cookies',
                'help-text' => 'Leer lassen, um einen eigenen Cookie-Namen für diesen Realm zu erzeugen. Nach einer Änderung ist eine erneute Anmeldung erforderlich.',
            ],
            'session-absolute-lifetime' => [
                'label' => 'Maximale Sitzungsdauer',
                'help-text' => 'Maximales Alter einer Authentifizierungssitzung in Sekunden.',
            ],
            'session-token-ttl' => [
                'label' => 'Laufzeit des Sitzungstokens',
                'help-text' => 'Laufzeit des zu einer Sitzung gehörenden Tokens in Sekunden.',
            ],
            'session-token-refresh-skew' => [
                'label' => 'Erneuerungsfenster für Sitzungstokens',
                'help-text' => 'Anzahl der Sekunden vor Ablauf, ab der ein Sitzungstoken erneuert wird.',
            ],
            'challenge-providers' => [
                'label' => 'Verfügbare zweite Faktoren',
                'help-text' => 'Faktoren, mit denen Benutzer eine Mehrfaktor-Prüfung abschließen können.',
            ],
            'totp-secret-length' => [
                'label' => 'Länge des TOTP-Geheimnisses',
                'help-text' => 'Länge neu erzeugter Authenticator-Geheimnisse: 16 bis 64 Zeichen in Schritten von 8.',
            ],
            'totp-window' => [
                'label' => 'TOTP-Zeittoleranz',
                'help-text' => 'Akzeptierte Zeitabweichung in Schritten von 30 Sekunden.',
            ],
            'recovery-codes' => [
                'label' => 'Anzahl der Wiederherstellungscodes',
                'help-text' => 'Anzahl der Codes beim Erstellen neuer Wiederherstellungscodes.',
            ],
            'dynamic-registration' => [
                'label' => 'Dynamische Client-Registrierung erlauben',
                'help-text' => 'Anwendungen dürfen sich über den Registrierungsendpunkt des Realms anmelden.',
            ],
            'token-exchange' => [
                'label' => 'Token-Austausch erlauben',
                'help-text' => 'Clients dürfen Tokens innerhalb dieses Realms austauschen.',
            ],
            'first-party-trusted' => [
                'label' => 'Dem First-Party-Client vertrauen',
                'help-text' => 'Zustimmung für den First-Party-Client überspringen, sofern einer eingerichtet ist.',
            ],
            'allowed-redirect-schemes' => [
                'label' => 'Erlaubte eigene Weiterleitungsschemata',
                'help-text' => 'Ein Schema pro Zeile, ohne Doppelpunkt. Leer lassen, um nur HTTP und HTTPS zu erlauben.',
            ],
            'allowed-redirect-domains' => [
                'label' => 'Erlaubte Weiterleitungsdomains',
                'help-text' => 'Ein exakter Host pro Zeile. Mit * alle Hosts erlauben. Eine leere Liste erlaubt keine HTTP- oder HTTPS-Weiterleitungshosts.',
            ],
            'default-scopes' => [
                'label' => 'Standard-Scopes',
                'help-text' => 'Erlaubte Scopes für dynamisch registrierte Clients, ein Scope pro Zeile. Eine leere Liste erlaubt alle verfügbaren Scopes.',
            ],
            'trusted-clients' => [
                'label' => 'Vertrauenswürdige Clients',
                'help-text' => 'Eine Client-Kennung aus diesem Realm pro Zeile. Diese Clients überspringen die Zustimmung.',
            ],
        ],
    ],
];

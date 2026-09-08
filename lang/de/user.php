<?php
declare(strict_types=1);

return [
    'title' => 'Profil',
    'sections' => [
        'login' => 'Anmeldung',
    ],
    'profile' => [
        'subtitle' => 'Aktualisiere deinen Namen und deine E-Mail-Adresse.',
        'updated' => 'Profil aktualisiert.',
        'unverified' => 'Deine E-Mail-Adresse ist nicht bestätigt.',
        'email' => [
            'unverified' => 'Nicht bestätigt',
        ],
        'verification-sent' => 'Ein neuer Bestätigungslink wurde an deine E-Mail-Adresse gesendet.',
        'delete-account' => [
            'heading' => 'Konto löschen',
            'description' => 'Lösche dein Konto und alle zugehörigen Ressourcen. Diese Aktion kann nicht rückgängig gemacht werden. Gib dein Passwort zur Bestätigung ein.',
            'submit' => 'Konto löschen',
        ],
    ],
    'security' => [
        'heading' => 'Sicherheit',
        'subtitle' => 'Aktualisiere dein Passwort und verwalte die Anmeldesicherheit.',
        'confirm' => [
            'description' => 'Bestätige dein Passwort, um deine Sicherheitseinstellungen anzuzeigen und zu verwalten.',
            'action' => 'Sicherheitseinstellungen entsperren',
        ],
        'password' => [
            'current' => 'Aktuelles Passwort',
            'new' => 'Neues Passwort',
            'updated' => 'Passwort aktualisiert.',
        ],
        'two-factor' => [
            'heading' => 'Zwei-Faktor-Authentifizierung',
            'status' => [
                'enabled' => 'Aktiv',
                'disabled' => 'Nicht aktiv',
            ],
            'add-method' => 'Methode hinzufügen',
            'description' => [
                'enabled' => 'Bei der Anmeldung fragen wir nach dem Passwort zusätzlich nach einer dieser Methoden.',
                'disabled' => 'Nur dein Passwort schützt dieses Konto. Füge eine Methode hinzu, um bei der Anmeldung einen zweiten Schritt zu verlangen.',
            ],
            'setup-title' => 'Zweiten Faktor hinzufügen',
        ],
        'recovery-codes' => [
            'heading' => 'Wiederherstellungscodes',
        ],
    ],
    'preferences' => [
        'heading' => 'Einstellungen',
        'subtitle' => 'Wähle dein Design, deine Sprache und deine Zeitzone.',
        'theme' => [
            'label' => 'Darstellung',
            'light' => 'Hell',
            'dark' => 'Dunkel',
            'system' => 'System',
        ],
        'language' => [
            'label' => 'Sprache',
        ],
        'timezone' => [
            'label' => 'Zeitzone',
            'placeholder' => 'Zeitzone auswählen',
        ],
        'notifications' => [
            'heading' => 'Benachrichtigungen',
            'summary' => ':count/2 aktiviert',
            'tenant-invitations' => [
                'label' => 'Mandanteneinladungen',
                'help-text' => 'Benachrichtige mich in der App, wenn ich zu einem Mandanten eingeladen werde.',
            ],
            'tenant-membership' => [
                'label' => 'Änderungen der Mandantenmitgliedschaft',
                'help-text' => 'Benachrichtige mich in der App, wenn ich aus einem Mandanten entfernt werde.',
            ],
        ],
        'updated' => 'Einstellungen aktualisiert.',
    ],
];

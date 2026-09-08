<?php
declare(strict_types=1);

return [
    'permissions' => [
        'admin' => [
            'access' => 'Auf den Admin-Bereich zugreifen',
            'users' => [
                'manage' => 'Benutzer einsehen und verwalten',
            ],
            'tenants' => [
                'manage' => 'Mandanten einsehen und verwalten',
            ],
            'roles' => [
                'manage' => 'Festlegen, welche Benutzer Admins sind',
            ],
        ],
    ],
    'dashboard' => [
        'heading' => 'Admin-Dashboard',
        'cards' => [
            'users' => [
                'title' => 'Benutzer',
            ],
            'tenants' => [
                'title' => 'Mandanten',
            ],
            'admins' => [
                'title' => 'Admins',
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
            'tenants' => 'Mandanten',
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
            'tenants' => [
                'heading' => 'Mandantenmitgliedschaften',
            ],
            'no-tenants' => 'Dieser Benutzer ist kein Mitglied eines Mandanten.',
            'no-roles' => 'Keine Rollen zugewiesen.',
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
    'tenants' => [
        'heading' => 'Mandanten',
        'columns' => [
            'name' => 'Name',
            'slug' => 'Slug',
            'personal' => 'Persönlich',
            'members' => 'Mitglieder',
            'owner' => 'Inhaber',
            'created-at' => 'Erstellt',
        ],
        'actions' => [
            'manage' => 'Verwalten',
        ],
        'detail' => [
            'summary' => [
                'heading' => 'Übersicht',
            ],
            'slug' => 'Slug',
            'personal' => 'Persönlicher Mandant',
            'owner' => 'Inhaber',
            'no-owner' => 'Kein Inhaber zugewiesen.',
            'created-at' => 'Erstellt',
            'members' => [
                'heading' => 'Mitglieder',
            ],
            'no-members' => 'Dieser Mandant hat keine Mitglieder.',
            'no-roles' => 'Keine Rollen zugewiesen.',
        ],
    ],
];

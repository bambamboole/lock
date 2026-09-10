<?php
declare(strict_types=1);

return [
    'fields' => [
        'name' => 'Realm-Name',
    ],
    'api' => [
        'no-realm-context' => 'Dieses Token ist an keinen Realm gebunden.',
    ],
    'permissions' => [
        'realm' => [
            'update' => 'Realm-Einstellungen ändern',
            'delete' => 'Realm löschen',
        ],
        'member' => [
            'view' => 'Realm-Mitglieder und Einladungen ansehen',
            'manage' => 'Realm-Mitglieder und Einladungen verwalten',
        ],
        'audit' => [
            'view' => 'Aktivitätsprotokoll des Realms einsehen',
        ],
        'role' => [
            'manage' => 'Rollen des Realms verwalten',
        ],
        'realm-settings' => [
            'manage' => 'Authentifizierung konfigurieren',
        ],
    ],
    'roles' => [
        'owner' => 'Eigentümer',
        'admin' => 'Administrator',
        'member' => 'Mitglied',
    ],
    'onboarding' => [
        'title' => 'Workspace einrichten',
        'heading' => 'Lege deinen Workspace an',
        'subtitle' => 'Jedes Konto arbeitet in einem Workspace. Gib deinem einen Namen, um loszulegen – weitere anlegen und andere einladen kannst du später.',
    ],
    'create' => [
        'title' => 'Neuen Realm erstellen',
        'menu' => [
            'label' => 'Neuen Realm erstellen',
        ],
        'submit' => 'Realm erstellen',
        'created' => 'Realm erstellt.',
        'name' => [
            'placeholder' => 'Mein Realm',
        ],
    ],
    'update' => [
        'submit' => 'Realm speichern',
        'updated' => 'Realm aktualisiert.',
    ],
    'delete' => [
        'warning' => 'Bitte sei vorsichtig, dies kann nicht rückgängig gemacht werden.',
        'confirm-name' => 'Realm-Namen bestätigen',
        'name-mismatch' => 'Der Realm-Name stimmt nicht überein.',
        'submit' => 'Realm löschen',
        'deleted' => 'Realm gelöscht.',
    ],
    'invite' => [
        'submit' => 'Einladung senden',
        'sent' => 'Einladung gesendet.',
        'email' => [
            'placeholder' => 'kollege@example.com',
        ],
        'already-member' => 'Diese E-Mail-Adresse ist bereits Mitglied oder hat eine ausstehende Einladung.',
    ],
    'switch' => [
        'label' => 'Realm wechseln',
        'switched' => 'Zu :realm gewechselt.',
    ],
    'members' => [
        'column' => 'Mitglied',
        'actions' => [
            'label' => 'Mitglieder-Aktionen',
        ],
        'remove' => [
            'label' => 'Entfernen',
            'confirm' => [
                'title' => 'Mitglied entfernen?',
                'description' => 'Dieser Benutzer verliert den Zugriff auf den Realm.',
                'label' => 'Mitglied entfernen',
            ],
        ],
        'owner-cannot-be-removed' => 'Der Realm-Eigentümer kann nicht entfernt werden.',
        'owner-role-cannot-be-changed' => 'Die Rolle des Realm-Eigentümers kann nicht geändert werden.',
        'removed' => 'Mitglied entfernt.',
        'change-role' => 'Rolle ändern',
        'role-updated' => 'Mitglieder-Rolle aktualisiert.',
    ],
    'invitations' => [
        'column' => 'Einladung',
        'sent-column' => 'Gesendet',
        'actions' => [
            'label' => 'Einladungs-Aktionen',
        ],
        'cancel' => [
            'label' => 'Abbrechen',
            'confirm' => [
                'title' => 'Einladung zurückziehen?',
                'description' => 'Der Einladungslink wird ungültig.',
                'label' => 'Einladung zurückziehen',
            ],
        ],
        'cancelled' => 'Einladung zurückgezogen.',
        'resend' => 'Erneut senden',
        'resent' => 'Einladung erneut gesendet.',
    ],
    'notification' => [
        'subject' => 'Du wurdest eingeladen, :realm beizutreten',
        'line' => ':inviter hat dich eingeladen, dem Realm :realm beizutreten.',
        'action' => 'Einladung annehmen',
        'invitation' => [
            'title' => 'Neue Realm-Einladung',
            'body' => ':inviter hat dich eingeladen, :realm beizutreten.',
        ],
        'removed' => [
            'title' => 'Aus Realm entfernt',
            'body' => 'Du wurdest aus :realm entfernt.',
        ],
    ],
    'accept' => [
        'wrong-email' => 'Diese Einladung wurde an eine andere E-Mail-Adresse gesendet.',
        'already-accepted' => 'Diese Einladung wurde bereits angenommen.',
        'expired' => 'Diese Einladung ist abgelaufen.',
    ],
    'settings' => [
        'subtitle' => 'Verwalte Realm-Einstellungen, Mitglieder und Einladungen.',
        'tabs' => [
            'general' => 'Allgemein',
            'members' => 'Mitglieder',
            'roles' => 'Rollen',
            'activity' => 'Aktivität',
            'danger' => 'Gefahrenzone',
            'configuration' => 'Konfiguration',
        ],
        'general' => [
            'heading' => 'Realm-Details',
            'subtitle' => 'Aktualisiere den Namen deines Realms.',
        ],
        'members' => [
            'heading' => 'Realm-Mitglieder',
            'subtitle' => 'Verwalte, wer zu diesem Realm gehört.',
            'invite' => [
                'heading' => 'Mitglied einladen',
                'subtitle' => 'Sende eine Einladung, um jemanden zu diesem Realm hinzuzufügen.',
            ],
            'invitations' => [
                'heading' => 'Ausstehende Einladungen',
                'subtitle' => 'Einladungen, die noch nicht angenommen wurden.',
            ],
        ],
        'roles' => [
            'heading' => 'Rollen',
            'subtitle' => 'Definiere benutzerdefinierte Rollen und lege fest, welche Berechtigungen sie gewähren. Die Berechtigung „Rollen verwalten“ erlaubt es, die Berechtigungen jeder Rolle zu ändern, auch die eigenen — vergib sie mit Bedacht.',
            'create' => [
                'label' => 'Rolle erstellen',
                'heading' => 'Rolle erstellen',
            ],
            'edit' => [
                'label' => 'Bearbeiten',
                'heading' => 'Rolle bearbeiten',
            ],
            'save' => 'Rolle speichern',
            'name' => [
                'placeholder' => 'z. B. Support',
            ],
            'permissions' => 'Berechtigungen',
            'permissions-help' => 'Wähle aus, welche Aktionen diese Rolle ausführen darf.',
            'column' => [
                'name' => 'Name',
                'members' => 'Mitglieder',
                'permissions' => 'Berechtigungen',
            ],
            'actions' => [
                'label' => 'Rollen-Aktionen',
            ],
            'delete' => [
                'label' => 'Löschen',
                'confirm' => [
                    'title' => 'Rolle löschen?',
                    'description' => 'Mitglieder und Einladungen dürfen diese Rolle nicht verwenden.',
                    'label' => 'Rolle löschen',
                ],
            ],
            'created' => 'Rolle erstellt.',
            'updated' => 'Rolle aktualisiert.',
            'deleted' => 'Rolle gelöscht.',
            'in-use' => 'Diese Rolle ist Mitgliedern oder ausstehenden Einladungen zugewiesen und kann nicht gelöscht werden.',
        ],
        'activity' => [
            'heading' => 'Aktivität',
            'subtitle' => 'Änderungsprotokoll für diesen Realm.',
            'empty' => 'Noch keine Aktivität.',
            'detail' => [
                'empty' => 'Keine weiteren Details aufgezeichnet.',
            ],
            'columns' => [
                'when' => 'Wann',
                'actor' => 'Akteur',
                'event' => 'Ereignis',
                'description' => 'Beschreibung',
            ],
        ],
        'danger' => [
            'heading' => 'Realm löschen',
            'subtitle' => 'Lösche diesen Realm und alle zugehörigen Ressourcen dauerhaft.',
        ],
    ],
];

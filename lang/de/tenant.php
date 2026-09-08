<?php
declare(strict_types=1);

return [
    'fields' => [
        'name' => 'Mandanten-Name',
    ],
    'api' => [
        'no-tenant-context' => 'Dieses Token ist an keinen Mandanten gebunden.',
    ],
    'permissions' => [
        'tenant' => [
            'update' => 'Mandanten-Einstellungen ändern',
            'delete' => 'Mandanten löschen',
        ],
        'member' => [
            'view' => 'Mandantenmitglieder und Einladungen ansehen',
            'manage' => 'Mandantenmitglieder und Einladungen verwalten',
        ],
        'audit' => [
            'view' => 'Aktivitätsprotokoll des Mandanten einsehen',
        ],
        'role' => [
            'manage' => 'Rollen des Mandanten verwalten',
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
        'title' => 'Neuen Mandanten erstellen',
        'menu' => [
            'label' => 'Neuen Mandanten erstellen',
        ],
        'submit' => 'Mandanten erstellen',
        'created' => 'Mandant erstellt.',
        'name' => [
            'placeholder' => 'Mein Mandant',
        ],
    ],
    'update' => [
        'submit' => 'Mandanten speichern',
        'updated' => 'Mandant aktualisiert.',
    ],
    'delete' => [
        'warning' => 'Bitte sei vorsichtig, dies kann nicht rückgängig gemacht werden.',
        'confirm-name' => 'Mandanten-Namen bestätigen',
        'name-mismatch' => 'Der Mandanten-Name stimmt nicht überein.',
        'submit' => 'Mandanten löschen',
        'deleted' => 'Mandant gelöscht.',
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
        'label' => 'Mandanten wechseln',
        'switched' => 'Zu :tenant gewechselt.',
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
                'description' => 'Dieser Benutzer verliert den Zugriff auf den Mandanten.',
                'label' => 'Mitglied entfernen',
            ],
        ],
        'owner-cannot-be-removed' => 'Der Mandanten-Eigentümer kann nicht entfernt werden.',
        'owner-role-cannot-be-changed' => 'Die Rolle des Mandanten-Eigentümers kann nicht geändert werden.',
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
        'subject' => 'Du wurdest eingeladen, :tenant beizutreten',
        'line' => ':inviter hat dich eingeladen, dem Mandanten :tenant beizutreten.',
        'action' => 'Einladung annehmen',
        'invitation' => [
            'title' => 'Neue Mandanten-Einladung',
            'body' => ':inviter hat dich eingeladen, :tenant beizutreten.',
        ],
        'removed' => [
            'title' => 'Aus Mandant entfernt',
            'body' => 'Du wurdest aus :tenant entfernt.',
        ],
    ],
    'accept' => [
        'wrong-email' => 'Diese Einladung wurde an eine andere E-Mail-Adresse gesendet.',
        'already-accepted' => 'Diese Einladung wurde bereits angenommen.',
        'expired' => 'Diese Einladung ist abgelaufen.',
    ],
    'settings' => [
        'subtitle' => 'Verwalte Mandanten-Einstellungen, Mitglieder und Einladungen.',
        'tabs' => [
            'general' => 'Allgemein',
            'members' => 'Mitglieder',
            'roles' => 'Rollen',
            'activity' => 'Aktivität',
            'danger' => 'Gefahrenzone',
        ],
        'general' => [
            'heading' => 'Mandanten-Details',
            'subtitle' => 'Aktualisiere den Namen deines Mandanten.',
        ],
        'members' => [
            'heading' => 'Mandanten-Mitglieder',
            'subtitle' => 'Verwalte, wer zu diesem Mandanten gehört.',
            'invite' => [
                'heading' => 'Mitglied einladen',
                'subtitle' => 'Sende eine Einladung, um jemanden zu diesem Mandanten hinzuzufügen.',
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
            'subtitle' => 'Änderungsprotokoll für diesen Mandanten.',
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
            'heading' => 'Mandanten löschen',
            'subtitle' => 'Lösche diesen Mandanten und alle zugehörigen Ressourcen dauerhaft.',
        ],
    ],
];

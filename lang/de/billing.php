<?php
declare(strict_types=1);

return [
    'heading' => 'Abrechnung & Abonnement',
    'subheading' => 'Wählen Sie den passenden Tarif für Ihren Mandanten.',
    'current-plan' => 'Aktueller Tarif',
    'subscribe' => 'Abonnieren',
    'checkout' => [
        'invalid-price' => 'Dieser Tarif ist nicht verfügbar.',
        'already-subscribed' => 'Dieser Mandant hat bereits ein Abonnement. Ändern Sie stattdessen Ihren Tarif.',
    ],
    'transactions' => [
        'heading' => 'Transaktionen',
        'columns' => [
            'reference' => 'Referenz',
            'date' => 'Datum',
            'status' => 'Status',
            'total' => 'Gesamt',
        ],
    ],
    'plans' => [
        'free' => [
            'name' => 'Free',
            'description' => 'Kostenlos starten.',
        ],
        'starter' => [
            'name' => 'Starter',
            'description' => 'Für wachsende Organisationen.',
        ],
        'professional' => [
            'name' => 'Professional',
            'description' => 'Für skalierende Organisationen.',
        ],
    ],
    'features' => [
        'tenants' => 'Mandanten',
        'community-support' => 'Community-Support',
        'unlimited-tenants' => 'Unbegrenzte Mandanten',
        'email-support' => 'E-Mail-Support',
        'priority-support' => 'Priorisierter Support',
        'audit-log' => 'Audit-Log',
    ],
    'rate-limit' => [
        'exceeded' => 'API-Anfragelimit überschritten. Bitte später erneut versuchen.',
    ],
    'manage' => [
        'heading' => 'Abonnement verwalten',
        'cancel' => [
            'label' => 'Abonnement kündigen',
            'confirm' => [
                'title' => 'Abonnement kündigen?',
                'description' => 'Ihr Mandant behält den Zugriff bis zum Ende des Abrechnungszeitraums.',
            ],
        ],
        'canceled' => 'Abonnement gekündigt.',
        'resume' => 'Abonnement fortsetzen',
        'resumed' => 'Abonnement fortgesetzt.',
        'update-payment-method' => 'Zahlungsmethode aktualisieren',
        'no-subscription' => 'Dieser Mandant hat kein aktives Abonnement.',
        'nothing-to-resume' => 'Dieses Abonnement ist weder gekündigt noch pausiert, es gibt also nichts fortzusetzen.',
        'provider-error' => 'Diese Aktion konnte gerade nicht ausgeführt werden. Bitte versuchen Sie es in Kürze erneut.',
    ],
    'switch' => [
        'upgrade' => [
            'label' => 'Auf :plan upgraden',
            'confirm' => [
                'title' => 'Auf :plan upgraden?',
                'description' => 'Die anteilige Differenz wird heute berechnet und der neue Tarif gilt sofort.',
            ],
        ],
        'downgrade' => [
            'label' => 'Auf :plan herabstufen',
            'confirm' => [
                'title' => 'Auf :plan herabstufen?',
                'description' => 'Die Änderung gilt ab der nächsten Verlängerung und das Guthaben wird auf diese Rechnung angerechnet.',
            ],
        ],
        'switched' => 'Ihr Tarif ist jetzt :plan.',
        'unavailable' => 'Dieser Tarifwechsel ist nicht verfügbar.',
    ],
    'state' => [
        'past-due' => [
            'title' => 'Zahlung fehlgeschlagen',
            'message' => [
                'default' => 'Wir konnten Ihre Zahlungsmethode nicht belasten. Aktualisieren Sie sie, damit :tenant weiterläuft.',
                'restricted' => 'Wir konnten Ihre Zahlungsmethode nicht belasten. Aktualisieren Sie sie, damit :tenant weiterläuft. Bitten Sie eine Mandanten-Inhaberin oder einen Mandanten-Inhaber, dies zu klären.',
            ],
            'badge' => 'Zahlung überfällig',
        ],
        'canceling' => [
            'title' => 'Abonnement endet',
            'message' => [
                'default' => 'Ihr Abonnement endet am {{date, datetime(dateStyle: long)}}. Setzen Sie es fort, um den Zugriff zu behalten.',
                'restricted' => 'Ihr Abonnement endet am {{date, datetime(dateStyle: long)}}. Setzen Sie es fort, um den Zugriff zu behalten. Bitten Sie eine Mandanten-Inhaberin oder einen Mandanten-Inhaber, dies zu klären.',
            ],
            'badge' => 'Endet am :date',
        ],
        'trial-ending' => [
            'title' => 'Testphase endet',
            'message' => [
                'default' => 'Ihre Testphase endet am {{date, datetime(dateStyle: long)}}. Wählen Sie einen Tarif, um den Zugriff zu behalten.',
                'restricted' => 'Ihre Testphase endet am {{date, datetime(dateStyle: long)}}. Wählen Sie einen Tarif, um den Zugriff zu behalten. Bitten Sie eine Mandanten-Inhaberin oder einen Mandanten-Inhaber, dies zu klären.',
            ],
        ],
        'expired' => [
            'title' => 'Kein aktives Abonnement',
            'message' => [
                'default' => 'Dieser Mandant hat kein aktives Abonnement. Wählen Sie einen Tarif, um fortzufahren.',
                'restricted' => 'Dieser Mandant hat kein aktives Abonnement. Wählen Sie einen Tarif, um fortzufahren. Bitten Sie eine Mandanten-Inhaberin oder einen Mandanten-Inhaber, dies zu klären.',
            ],
        ],
        'view-plans' => 'Tarife ansehen',
    ],
];

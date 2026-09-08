<?php

declare(strict_types=1);

return [
    'currency' => env('CASHIER_CURRENCY', 'EUR'),

    'trial-days' => 14,

    'trial-warning-days' => 3,

    'plans' => [
        'free' => [
            'price' => 0,
            'listed-features' => [
                'billing.features.tenants',
                'billing.features.community-support',
            ],
        ],
        'starter' => [
            'price' => 19,
            'monthly' => env('PADDLE_PRICE_STARTER_MONTHLY'),
            'yearly' => env('PADDLE_PRICE_STARTER_YEARLY'),
            'listed-features' => [
                'billing.features.unlimited-tenants',
                'billing.features.email-support',
            ],
        ],
        'professional' => [
            'price' => 99,
            'monthly' => env('PADDLE_PRICE_PROFESSIONAL_MONTHLY'),
            'yearly' => env('PADDLE_PRICE_PROFESSIONAL_YEARLY'),
            'listed-features' => [
                'billing.features.priority-support',
                'billing.features.audit-log',
            ],
        ],
    ],

    'api-limits' => [
        'trial' => ['rate' => 300, 'burst' => 100],
        'free' => ['rate' => 60, 'burst' => 30],
        'starter' => ['rate' => 300, 'burst' => 100],
        'professional' => ['rate' => 1000, 'burst' => 300],
    ],
];

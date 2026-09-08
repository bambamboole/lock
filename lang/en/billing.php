<?php
declare(strict_types=1);

return [
    'heading' => 'Billing & subscription',
    'subheading' => 'Choose the plan that fits your tenant.',
    'current-plan' => 'Current plan',
    'subscribe' => 'Subscribe',
    'checkout' => [
        'invalid-price' => 'That plan is not available.',
        'already-subscribed' => 'This tenant already has a subscription. Change your plan instead.',
    ],
    'transactions' => [
        'heading' => 'Transactions',
        'columns' => [
            'reference' => 'Reference',
            'date' => 'Date',
            'status' => 'Status',
            'total' => 'Total',
        ],
    ],
    'plans' => [
        'free' => [
            'name' => 'Free',
            'description' => 'Get started at no cost.',
        ],
        'starter' => [
            'name' => 'Starter',
            'description' => 'For growing organisations.',
        ],
        'professional' => [
            'name' => 'Professional',
            'description' => 'For scaling organisations.',
        ],
    ],
    'features' => [
        'tenants' => 'Tenants',
        'community-support' => 'Community support',
        'unlimited-tenants' => 'Unlimited tenants',
        'email-support' => 'Email support',
        'priority-support' => 'Priority support',
        'audit-log' => 'Audit log',
    ],
    'rate-limit' => [
        'exceeded' => 'API rate limit exceeded. Please retry later.',
    ],
    'manage' => [
        'heading' => 'Manage subscription',
        'cancel' => [
            'label' => 'Cancel subscription',
            'confirm' => [
                'title' => 'Cancel subscription?',
                'description' => 'Your tenant keeps access until the end of the billing period.',
            ],
        ],
        'canceled' => 'Subscription canceled.',
        'resume' => 'Resume subscription',
        'resumed' => 'Subscription resumed.',
        'update-payment-method' => 'Update payment method',
        'no-subscription' => 'This tenant has no active subscription.',
        'nothing-to-resume' => 'This subscription is not canceled or paused, so there is nothing to resume.',
        'provider-error' => 'We could not complete that action right now. Please try again in a moment.',
    ],
    'switch' => [
        'upgrade' => [
            'label' => 'Upgrade to :plan',
            'confirm' => [
                'title' => 'Upgrade to :plan?',
                'description' => 'You will be charged the prorated difference today and the new plan applies immediately.',
            ],
        ],
        'downgrade' => [
            'label' => 'Downgrade to :plan',
            'confirm' => [
                'title' => 'Downgrade to :plan?',
                'description' => 'The change applies at your next renewal and the credit is applied to that invoice.',
            ],
        ],
        'switched' => 'Your plan is now :plan.',
        'unavailable' => 'That plan change is not available.',
    ],
    'state' => [
        'past-due' => [
            'title' => 'Payment failed',
            'message' => [
                'default' => 'We could not charge your payment method. Update it to keep :tenant running.',
                'restricted' => 'We could not charge your payment method. Update it to keep :tenant running. Ask a tenant owner to resolve this.',
            ],
            'badge' => 'Payment past due',
        ],
        'canceling' => [
            'title' => 'Subscription ending',
            'message' => [
                'default' => 'Your subscription ends on {{date, datetime(dateStyle: long)}}. Resume it to keep your access.',
                'restricted' => 'Your subscription ends on {{date, datetime(dateStyle: long)}}. Resume it to keep your access. Ask a tenant owner to resolve this.',
            ],
            'badge' => 'Cancels on :date',
        ],
        'trial-ending' => [
            'title' => 'Trial ending',
            'message' => [
                'default' => 'Your trial ends on {{date, datetime(dateStyle: long)}}. Choose a plan to keep your access.',
                'restricted' => 'Your trial ends on {{date, datetime(dateStyle: long)}}. Choose a plan to keep your access. Ask a tenant owner to resolve this.',
            ],
        ],
        'expired' => [
            'title' => 'No active subscription',
            'message' => [
                'default' => 'This tenant has no active subscription. Choose a plan to continue.',
                'restricted' => 'This tenant has no active subscription. Choose a plan to continue. Ask a tenant owner to resolve this.',
            ],
        ],
        'view-plans' => 'View plans',
    ],
];

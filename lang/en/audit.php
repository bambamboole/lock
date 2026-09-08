<?php
declare(strict_types=1);

return [
    'events' => [
        'model' => [
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
        ],
        'tenant' => ['updated' => 'Tenant updated'],
        'invitation' => [
            'created' => 'Invitation sent',
            'cancelled' => 'Invitation cancelled',
            'resent' => 'Invitation resent',
        ],
        'member' => [
            'role-changed' => 'Member role changed',
            'removed' => 'Member removed',
        ],
        'api-token' => [
            'created' => 'API token created',
            'revoked' => 'API token revoked',
        ],
        'role' => [
            'created' => 'Role created',
            'updated' => 'Role updated',
            'deleted' => 'Role deleted',
        ],
        'admin' => [
            'roles' => [
                'updated' => 'Global roles updated',
                'granted' => 'Super Admin granted',
            ],
            'password-reset' => ['sent' => 'Password reset sent'],
            'verification' => ['resent' => 'Verification email resent'],
        ],
    ],
];

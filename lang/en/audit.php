<?php
declare(strict_types=1);

return [
    'categories' => [
        'auth' => 'Authentication',
        'oauth' => 'OAuth',
        'admin' => 'Administration',
    ],
    'types' => [
        'auth' => [
            'login' => [
                'succeeded' => 'Sign-in succeeded',
                'failed' => 'Sign-in failed',
            ],
            'logout' => 'Signed out',
            'password' => ['reset' => 'Password reset'],
            'registration' => ['succeeded' => 'Registration completed'],
            'mfa' => [
                'challenge-succeeded' => 'Second factor accepted',
                'challenge-failed' => 'Second factor rejected',
                'factor-enrollment-started' => 'Factor enrollment started',
                'factor-confirmed' => 'Factor confirmed',
                'factor-revoked' => 'Factor revoked',
                'recovery-code-used' => 'Recovery code used',
            ],
        ],
        'oauth' => [
            'token' => [
                'issued' => 'Token issued',
                'failed' => 'Token request failed',
                'revoked' => 'Token revoked',
            ],
            'consent' => [
                'approved' => 'Consent granted',
                'denied' => 'Consent denied',
            ],
            'client-auth' => ['failed' => 'Client authentication failed'],
        ],
        'admin' => [
            'client' => [
                'provisioned' => 'Client provisioned',
                'registered' => 'Client registered',
            ],
            'keys' => ['rotated' => 'Signing keys rotated'],
        ],
    ],
    'events' => [
        'model' => [
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
        ],
        'realm' => [
            'created' => 'Realm created',
            'updated' => 'Realm updated',
            'deleted' => 'Realm deleted',
            'client' => [
                'created' => 'Client created',
                'updated' => 'Client updated',
                'secret-rotated' => 'Client secret rotated',
                'revoked' => 'Client blocked',
                'restored' => 'Client unblocked',
                'deleted' => 'Client deleted',
            ],
            'role' => [
                'created' => 'Realm role created',
                'updated' => 'Realm role updated',
                'deleted' => 'Realm role deleted',
            ],
            'user' => [
                'roles-updated' => 'Realm roles updated',
            ],
        ],
        'admin' => [
            'roles' => [
                'updated' => 'Global roles updated',
                'granted' => 'Super Admin granted',
            ],
            'password-reset' => ['sent' => 'Password reset sent'],
            'verification' => ['resent' => 'Verification email resent'],
            'user' => [
                'created' => 'User created',
                'updated' => 'User updated',
                'blocked' => 'User blocked',
                'unblocked' => 'User unblocked',
                'deleted' => 'User deleted',
                'mfa-reset' => 'Multi-factor authentication reset',
                'session-ended' => 'Session ended',
                'sessions-ended' => 'All sessions ended',
            ],
        ],
    ],
];

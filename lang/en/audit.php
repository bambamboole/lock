<?php
declare(strict_types=1);

return [
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

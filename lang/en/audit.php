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

<?php
declare(strict_types=1);

return [
    'permissions' => [
        'admin' => [
            'access' => 'Access the admin area',
            'users' => [
                'manage' => 'View and manage users',
            ],
            'realms' => [
                'manage' => 'View and manage realms',
            ],
            'roles' => [
                'manage' => 'Manage which users are admins',
            ],
        ],
    ],
    'dashboard' => [
        'heading' => 'Admin dashboard',
        'cards' => [
            'users' => [
                'title' => 'Users',
            ],
            'realms' => [
                'title' => 'Realms',
            ],
            'admins' => [
                'title' => 'Admins',
            ],
        ],
    ],
    'activity' => [
        'heading' => 'Admin activity',
        'empty' => 'No admin activity yet.',
        'system-actor' => 'System (console)',
        'detail' => [
            'empty' => 'No additional details recorded.',
        ],
        'columns' => [
            'when' => 'When',
            'actor' => 'Actor',
            'event' => 'Event',
            'description' => 'Description',
        ],
    ],
    'users' => [
        'heading' => 'Users',
        'columns' => [
            'member' => 'Member',
            'realm' => 'Realm',
            'admin' => 'Admin',
            'created-at' => 'Joined',
        ],
        'actions' => [
            'manage' => 'Manage',
        ],
        'detail' => [
            'summary' => [
                'heading' => 'Summary',
            ],
            'created-at' => 'Joined',
            'realms' => [
                'heading' => 'Realm',
            ],
            'no-realms' => 'This user belongs to no realm.',
            'no-roles' => 'No roles assigned.',
            'admin-roles' => [
                'heading' => 'Admin roles',
            ],
            'account-actions' => [
                'heading' => 'Account actions',
            ],
            'roles' => [
                'field' => [
                    'label' => 'Global roles',
                ],
            ],
            'update-roles' => 'Update roles',
            'roles-updated' => 'Global roles updated.',
            'resend-verification' => 'Resend verification',
            'verification-sent' => 'Verification email sent.',
            'send-password-reset' => 'Send password reset',
            'password-reset-sent' => 'Password reset email sent.',
        ],
        'already-verified' => 'This user has already verified their email address.',
    ],
    'realms' => [
        'heading' => 'Realms',
        'columns' => [
            'name' => 'Name',
            'slug' => 'Slug',
            'users' => 'Users',
            'owner' => 'Owner',
            'created-at' => 'Created',
        ],
        'actions' => [
            'manage' => 'Manage',
        ],
        'detail' => [
            'summary' => [
                'heading' => 'Summary',
            ],
            'slug' => 'Slug',
            'owner' => 'Owner',
            'no-owner' => 'No owner assigned.',
            'created-at' => 'Created',
            'members' => [
                'heading' => 'Members',
            ],
            'no-members' => 'This realm has no members.',
            'no-roles' => 'No roles assigned.',
        ],
    ],
];

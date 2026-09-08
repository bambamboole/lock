<?php
declare(strict_types=1);

return [
    'permissions' => [
        'admin' => [
            'access' => 'Access the admin area',
            'users' => [
                'manage' => 'View and manage users',
            ],
            'tenants' => [
                'manage' => 'View and manage tenants',
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
            'tenants' => [
                'title' => 'Tenants',
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
            'tenants' => 'Tenants',
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
            'tenants' => [
                'heading' => 'Tenant memberships',
            ],
            'no-tenants' => 'This user is not a member of any tenant.',
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
    'tenants' => [
        'heading' => 'Tenants',
        'columns' => [
            'name' => 'Name',
            'slug' => 'Slug',
            'personal' => 'Personal',
            'members' => 'Members',
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
            'personal' => 'Personal tenant',
            'owner' => 'Owner',
            'no-owner' => 'No owner assigned.',
            'created-at' => 'Created',
            'members' => [
                'heading' => 'Members',
            ],
            'no-members' => 'This tenant has no members.',
            'no-roles' => 'No roles assigned.',
        ],
    ],
];

<?php
declare(strict_types=1);

return [
    'title' => 'Profile',
    'sections' => [
        'login' => 'Sign-in',
    ],
    'profile' => [
        'subtitle' => 'Update your name and email address.',
        'updated' => 'Profile updated.',
        'unverified' => 'Your email address is unverified.',
        'email' => [
            'unverified' => 'Unverified',
        ],
        'verification-sent' => 'A new verification link has been sent to your email address.',
        'delete-account' => [
            'heading' => 'Delete account',
            'description' => 'Delete your account and all of its resources. This action cannot be undone. Enter your password to confirm.',
            'submit' => 'Delete account',
        ],
    ],
    'security' => [
        'heading' => 'Security',
        'subtitle' => 'Update your password and manage sign-in security.',
        'confirm' => [
            'description' => 'Confirm your password to view and manage your security settings.',
            'action' => 'Unlock security settings',
        ],
        'password' => [
            'current' => 'Current password',
            'new' => 'New password',
            'updated' => 'Password updated.',
        ],
        'two-factor' => [
            'heading' => 'Two-factor authentication',
            'status' => [
                'enabled' => 'Active',
                'disabled' => 'Not active',
            ],
            'description' => [
                'enabled' => 'Signing in asks for one of these methods after your password.',
                'disabled' => 'Only your password protects this account. Add a method to require a second step at sign-in.',
            ],
            'add-method' => 'Add method',
            'setup-title' => 'Add a second factor',
        ],
        'recovery-codes' => [
            'heading' => 'Recovery codes',
        ],
    ],
    'preferences' => [
        'heading' => 'Preferences',
        'subtitle' => 'Choose your theme, language, and timezone.',
        'theme' => [
            'label' => 'Appearance',
            'light' => 'Light',
            'dark' => 'Dark',
            'system' => 'System',
        ],
        'language' => [
            'label' => 'Language',
        ],
        'timezone' => [
            'label' => 'Timezone',
            'placeholder' => 'Select a timezone',
        ],
        'notifications' => [
            'heading' => 'Notifications',
            'summary' => ':count/2 enabled',
            'tenant-invitations' => [
                'label' => 'Tenant invitations',
                'help-text' => 'Notify me in-app when I am invited to a tenant.',
            ],
            'tenant-membership' => [
                'label' => 'Tenant membership changes',
                'help-text' => 'Notify me in-app when I am removed from a tenant.',
            ],
        ],
        'updated' => 'Preferences updated.',
    ],
];

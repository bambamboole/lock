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
                'view' => 'View realms',
                'manage' => 'View and manage realms',
            ],
            'roles' => [
                'manage' => 'Manage which users are admins',
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
        'description' => 'Users of :realm.',
        'columns' => [
            'member' => 'Member',
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
        'description' => 'Every realm is an isolated identity boundary with its own users, clients and authentication settings.',
        'columns' => [
            'name' => 'Name',
            'slug' => 'Slug',
            'users' => 'Users',
            'created-at' => 'Created',
        ],
        'actions' => [
            'manage' => 'Manage',
        ],
        'created' => 'Realm created.',
        'updated' => 'Realm updated.',
        'configuration-saved' => 'Realm configuration saved.',
        'deleted' => 'Realm deleted.',
        'settings' => [
            'heading' => 'Realm settings',
            'description' => 'Name, authentication configuration and deletion of :realm.',
        ],
        'tabs' => [
            'general' => 'General',
            'configuration' => 'Configuration',
            'danger' => 'Danger',
        ],
        'summary' => [
            'heading' => 'Summary',
            'slug' => 'Slug',
            'users' => 'Users',
            'created-at' => 'Created',
        ],
        'general' => [
            'heading' => 'Realm details',
            'subtitle' => 'Rename the realm. Its identifier is fixed for life.',
            'submit' => 'Save realm',
        ],
        'danger' => [
            'heading' => 'Delete realm',
            'subtitle' => 'Deletes the realm together with its users, clients, sessions and tokens. This cannot be undone.',
            'confirm-name' => 'Confirm realm name',
            'name-mismatch' => 'The realm name does not match.',
            'submit' => 'Delete realm',
        ],
        'create' => [
            'heading' => 'Create realm',
            'description' => 'New realms start with the default authentication settings. You can configure them after creating the realm.',
            'submit' => 'Create realm',
        ],
        'sections' => [
            'general' => 'General',
            'tokens' => 'Token lifetimes',
            'sessions' => 'Sessions',
            'mfa' => 'Multi-factor authentication',
            'clients' => 'Client rules',
        ],
        'factors' => [
            'totp' => 'Authenticator app (TOTP)',
            'webauthn' => 'Security key or passkey (WebAuthn)',
        ],
        'fields' => [
            'name' => [
                'label' => 'Realm name',
            ],
            'slug' => [
                'label' => 'Realm identifier',
                'help-text' => 'Used in realm URLs. Lowercase letters, numbers and single hyphens only. Cannot be changed later.',
            ],
            'access-token-lifetime' => [
                'label' => 'Access token lifetime',
                'help-text' => 'Lifetime in seconds for newly issued access tokens.',
            ],
            'id-token-lifetime' => [
                'label' => 'ID token lifetime',
                'help-text' => 'Lifetime in seconds for newly issued ID tokens.',
            ],
            'client-credentials-lifetime' => [
                'label' => 'Machine-to-machine token lifetime',
                'help-text' => 'Lifetime in seconds for client credentials tokens.',
            ],
            'refresh-token-lifetime' => [
                'label' => 'Refresh token lifetime',
                'help-text' => 'Lifetime in seconds for newly issued refresh tokens.',
            ],
            'session-cookie-name' => [
                'label' => 'Session cookie name',
                'help-text' => 'Leave empty to generate a separate cookie name for this realm. Changing it requires signing in again.',
            ],
            'session-absolute-lifetime' => [
                'label' => 'Maximum session lifetime',
                'help-text' => 'Maximum age of an authentication session in seconds.',
            ],
            'session-token-ttl' => [
                'label' => 'Session token lifetime',
                'help-text' => 'Lifetime in seconds for the token associated with a session.',
            ],
            'session-token-refresh-skew' => [
                'label' => 'Session token renewal window',
                'help-text' => 'How many seconds before expiry a session token is renewed.',
            ],
            'challenge-providers' => [
                'label' => 'Available second factors',
                'help-text' => 'Factors users can use to complete a multi-factor challenge.',
            ],
            'totp-secret-length' => [
                'label' => 'TOTP secret length',
                'help-text' => 'Length of newly generated authenticator secrets: 16 to 64 characters, in steps of 8.',
            ],
            'totp-window' => [
                'label' => 'TOTP clock tolerance',
                'help-text' => 'Accepted clock drift in 30-second steps.',
            ],
            'recovery-codes' => [
                'label' => 'Recovery code count',
                'help-text' => 'Number of codes generated when users create recovery codes.',
            ],
            'dynamic-registration' => [
                'label' => 'Allow dynamic client registration',
                'help-text' => 'Allow applications to register through the realm registration endpoint.',
            ],
            'token-exchange' => [
                'label' => 'Allow token exchange',
                'help-text' => 'Allow clients to exchange tokens within this realm.',
            ],
            'first-party-trusted' => [
                'label' => 'Trust the first-party client',
                'help-text' => 'Skip consent for the first-party client when one is configured.',
            ],
            'allowed-redirect-schemes' => [
                'label' => 'Allowed custom redirect schemes',
                'help-text' => 'One scheme per line, without a colon. Leave empty to allow only HTTP and HTTPS schemes.',
            ],
            'allowed-redirect-domains' => [
                'label' => 'Allowed redirect domains',
                'help-text' => 'One exact host per line. Use * to allow any host. An empty list allows no HTTP or HTTPS redirect hosts.',
            ],
            'default-scopes' => [
                'label' => 'Default scopes',
                'help-text' => 'Scopes every new client gets and is granted without requesting them, one per line.',
            ],
            'optional-scopes' => [
                'label' => 'Optional scopes',
                'help-text' => 'Scopes every new client may request, one per line. A single * allows every scope in the catalog.',
            ],
            'trusted-clients' => [
                'label' => 'Trusted clients',
                'help-text' => 'One client identifier from this realm per line. These clients skip consent.',
            ],
        ],
    ],
];

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
        'created' => 'User created.',
        'updated' => 'User updated.',
        'columns' => [
            'member' => 'Member',
            'verified' => 'Verified',
            'mfa' => 'MFA',
            'blocked' => 'Blocked',
            'admin' => 'Admin',
            'created-at' => 'Joined',
        ],
        'filters' => [
            'verified' => 'Email verified',
            'blocked' => 'Blocked',
            'mfa' => 'MFA active',
        ],
        'actions' => [
            'manage' => 'Manage',
        ],
        'status' => [
            'active' => 'Active',
            'blocked' => 'Blocked',
            'mfa-enabled' => 'Enabled',
            'mfa-disabled' => 'Not enrolled',
        ],
        'create' => [
            'heading' => 'Create user',
            'description' => 'Adds a user to :realm. Invite them to choose their own password, or set one now.',
            'submit' => 'Create user',
        ],
        'fields' => [
            'credentials' => [
                'label' => 'Credentials',
                'invite' => 'Send an invitation email to set a password',
                'password' => 'Set an initial password',
            ],
            'email-verified' => [
                'label' => 'Email verified',
                'help-text' => 'A verified address skips the verification email.',
            ],
        ],
        'detail' => [
            'summary' => [
                'heading' => 'Summary',
            ],
            'created-at' => 'Joined',
            'blocked-at' => 'Blocked since',
            'status' => 'Status',
            'mfa' => 'Multi-factor authentication',
            'realm' => 'Realm',
            'realms' => [
                'heading' => 'Realm',
            ],
            'profile' => [
                'heading' => 'Profile',
                'subtitle' => 'Name, email address and verification state.',
                'submit' => 'Save user',
            ],
            'admin-roles' => [
                'heading' => 'Admin roles',
            ],
            'account-actions' => [
                'heading' => 'Account actions',
            ],
            'realm-roles' => [
                'heading' => 'Realm roles',
                'subtitle' => 'Issued as the roles claim in every ID and access token of this user.',
                'none' => 'No realm roles assigned.',
                'define' => 'Define the roles of this realm first',
                'field' => [
                    'label' => 'Roles',
                ],
                'update' => 'Update realm roles',
                'updated' => 'Realm roles updated.',
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
            'block' => [
                'label' => 'Block user',
                'confirm' => [
                    'title' => 'Block this user?',
                    'description' => 'The user can no longer sign in, and every active session and token stops working.',
                ],
                'done' => 'User blocked.',
            ],
            'unblock' => [
                'label' => 'Unblock user',
                'confirm' => [
                    'title' => 'Unblock this user?',
                    'description' => 'The user can sign in again.',
                ],
                'done' => 'User unblocked.',
            ],
            'reset-mfa' => [
                'label' => 'Reset MFA',
                'confirm' => [
                    'title' => 'Reset multi-factor authentication?',
                    'description' => 'Removes every authenticator app, recovery code and passkey. The user signs in with their password alone until they enroll again.',
                ],
                'done' => 'Multi-factor authentication reset.',
            ],
            'sessions' => [
                'heading' => 'Sessions',
                'subtitle' => 'Sign-in sessions of this user and the clients that took part in them.',
                'empty' => 'No sessions yet.',
                'not-active' => 'This session is no longer active.',
                'columns' => [
                    'sid' => 'Session',
                    'status' => 'Status',
                    'clients' => 'Clients',
                    'started-at' => 'Started',
                    'expires-at' => 'Expires',
                ],
                'status' => [
                    'active' => 'Active',
                    'expired' => 'Expired',
                    'revoked' => 'Ended',
                ],
                'end' => [
                    'label' => 'End',
                    'confirm' => [
                        'title' => 'End this session?',
                        'description' => 'Clients that took part in the session are told to log the user out.',
                    ],
                    'done' => 'Session ended.',
                ],
                'end-all' => [
                    'label' => 'End all sessions',
                    'confirm' => [
                        'title' => 'End all sessions?',
                        'description' => 'The user is signed out everywhere and clients are told to log them out.',
                    ],
                    'done' => ':count sessions ended.',
                ],
            ],
            'delete' => [
                'heading' => 'Delete user',
                'subtitle' => 'Deletes the user together with their sessions, tokens, consents and authentication factors. This cannot be undone.',
                'label' => 'Delete user',
                'confirm' => [
                    'title' => 'Delete this user?',
                    'description' => 'Everything the user owns in this realm is removed for good.',
                ],
                'done' => 'User deleted.',
            ],
        ],
        'already-verified' => 'This user has already verified their email address.',
        'already-blocked' => 'This user is already blocked.',
        'not-blocked' => 'This user is not blocked.',
    ],
    'clients' => [
        'heading' => 'Clients',
        'description' => 'OIDC clients of :realm.',
        'created' => 'Client created.',
        'updated' => 'Client updated.',
        'deleted' => 'Client deleted.',
        'columns' => [
            'name' => 'Name',
            'client-id' => 'Client ID',
            'type' => 'Type',
            'grant-types' => 'Grant types',
            'status' => 'Status',
        ],
        'actions' => [
            'manage' => 'Manage',
        ],
        'status' => [
            'active' => 'Active',
            'blocked' => 'Blocked',
        ],
        'type-badges' => [
            'public' => 'Public',
            'confidential' => 'Confidential',
        ],
        'types' => [
            'none' => 'Public (no secret, PKCE only)',
            'client-secret-basic' => 'Confidential (client_secret_basic)',
            'client-secret-post' => 'Confidential (client_secret_post)',
        ],
        'grant-types' => [
            'authorization-code' => 'Authorization code (PKCE)',
            'refresh-token' => 'Refresh token',
            'client-credentials' => 'Client credentials',
            'personal-access' => 'Personal access',
        ],
        'create' => [
            'heading' => 'Create client',
            'description' => 'The client is created in :realm with the realm\'s default and optional scopes.',
            'submit' => 'Create client',
        ],
        'sections' => [
            'general' => 'Client',
        ],
        'fields' => [
            'name' => [
                'label' => 'Name',
            ],
            'type' => [
                'label' => 'Client type',
                'help-text' => 'Confidential clients authenticate at the token endpoint with a secret; public clients rely on PKCE alone.',
            ],
            'grant-types' => [
                'label' => 'Grant types',
                'help-text' => 'Refresh tokens require the authorization code grant. Client credentials require a confidential client.',
            ],
            'redirect-uris' => [
                'label' => 'Redirect URIs',
                'help-text' => 'One absolute URI per line. Required for the authorization code grant.',
            ],
            'post-logout-redirect-uris' => [
                'label' => 'Post-logout redirect URIs',
                'help-text' => 'One absolute URI per line. Where the client may send users after logging out.',
            ],
            'consent-required' => [
                'label' => 'Require consent',
                'help-text' => 'Ask users to approve the requested scopes before issuing tokens.',
            ],
            'backchannel-logout-uri' => [
                'label' => 'Back-channel logout URI',
                'help-text' => 'Endpoint that receives a logout token when the user\'s session ends.',
            ],
        ],
        'validation' => [
            'refresh-token-needs-code' => 'The refresh token grant requires the authorization code grant.',
            'client-credentials-needs-secret' => 'The client credentials grant requires a confidential client.',
            'redirect-uri-required' => 'At least one redirect URI is required for the authorization code grant.',
            'invalid-uri' => 'The URI :uri must be absolute with a scheme and a host and no fragment.',
        ],
        'detail' => [
            'description' => 'Client of :realm.',
            'created-at' => 'Created',
            'scopes' => 'Scopes',
            'summary' => [
                'heading' => 'Summary',
            ],
            'secret' => [
                'heading' => 'Client secret',
                'subtitle' => 'Copy the secret now. It is shown only once and cannot be recovered.',
                'label' => 'Client secret',
            ],
            'settings' => [
                'heading' => 'Settings',
                'subtitle' => 'Name, type, grant types and URIs of the client.',
            ],
            'credentials' => [
                'heading' => 'Credentials',
                'subtitle' => 'Rotating the secret invalidates the current one immediately.',
            ],
            'danger' => [
                'heading' => 'Danger',
                'subtitle' => 'A blocked client cannot obtain new tokens until it is unblocked. Deleting removes its tokens, consents and sessions.',
            ],
            'rotate-secret' => [
                'label' => 'Rotate secret',
                'done' => 'Client secret rotated.',
                'confirm' => [
                    'title' => 'Rotate the client secret?',
                    'description' => 'The current secret stops working immediately. The new one is shown once.',
                ],
            ],
            'revoke' => [
                'label' => 'Block client',
                'done' => 'Client blocked.',
                'confirm' => [
                    'title' => 'Block this client?',
                    'description' => 'The client can no longer start logins or obtain tokens.',
                ],
            ],
            'restore' => [
                'label' => 'Unblock client',
                'done' => 'Client unblocked.',
                'confirm' => [
                    'title' => 'Unblock this client?',
                    'description' => 'The client can start logins and obtain tokens again.',
                ],
            ],
            'delete' => [
                'label' => 'Delete client',
                'confirm' => [
                    'title' => 'Delete this client?',
                    'description' => 'Deletes the client together with its tokens, consents and sessions. This cannot be undone.',
                ],
            ],
        ],
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
            'passwords' => 'Passwords',
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
            'password-min-length' => [
                'label' => 'Minimum password length',
                'help-text' => 'Characters a new password needs at least.',
            ],
            'password-history' => [
                'label' => 'Password history',
                'help-text' => 'Previous passwords a new password may not repeat, including the current one. 0 disables the check.',
            ],
            'password-max-age-days' => [
                'label' => 'Maximum password age',
                'help-text' => 'Days after which a password counts as expired. 0 means passwords never expire.',
            ],
            'password-mixed-case' => [
                'label' => 'Require upper and lower case letters',
                'help-text' => 'A new password must contain at least one upper and one lower case letter.',
            ],
            'password-numbers' => [
                'label' => 'Require a number',
                'help-text' => 'A new password must contain at least one digit.',
            ],
            'password-symbols' => [
                'label' => 'Require a symbol',
                'help-text' => 'A new password must contain at least one special character.',
            ],
            'password-uncompromised' => [
                'label' => 'Reject leaked passwords',
                'help-text' => 'Reject passwords found in a public data breach. This queries the Have I Been Pwned range API when a password is set.',
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
    'roles' => [
        'heading' => 'Roles',
        'description' => 'Realm roles of :realm, issued as the roles claim of every ID and access token.',
        'created' => 'Role created.',
        'updated' => 'Role updated.',
        'deleted' => 'Role deleted.',
        'columns' => [
            'name' => 'Name',
            'description' => 'Description',
            'users' => 'Users',
        ],
        'create' => [
            'label' => 'Create role',
        ],
        'edit' => [
            'label' => 'Edit',
        ],
        'delete' => [
            'label' => 'Delete',
            'confirm' => [
                'title' => 'Delete this role?',
                'description' => 'The role is removed from every user that holds it. Tokens already issued keep it until they expire.',
            ],
        ],
        'fields' => [
            'name' => [
                'label' => 'Name',
                'help-text' => 'The value relying parties receive in the roles claim, for example admin or member.',
            ],
            'description' => [
                'label' => 'Description',
            ],
        ],
        'validation' => [
            'name-format' => 'The name may only contain letters, digits, and the characters _ . : - and has to start with a letter or digit.',
        ],
    ],
];

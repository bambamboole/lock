<?php
declare(strict_types=1);

return [
    'social-providers' => [
        'heading' => 'Providers',
        'description' => 'Upstream identity providers this realm brokers sign-ins to. Add "Social provider" to the accepted sign-in methods to show them on the sign-in page.',
        'created' => 'Provider added.',
        'updated' => 'Provider updated.',
        'deleted' => 'Provider deleted.',
        'linking' => [
            'heading' => 'Account linking',
        ],
        'detail' => [
            'description' => ':driver provider of :realm.',
            'created-at' => 'Added',
            'key-fixed' => 'Part of the redirect URI and of the accounts linked through it, so it is fixed for life.',
            'settings' => [
                'heading' => 'Provider',
                'subtitle' => 'Open a row to change it. The key and the driver are fixed for life.',
            ],
            'secret' => [
                'set' => 'Stored',
                'missing' => 'Not set',
            ],
        ],
        'columns' => [
            'key' => 'Key',
            'driver' => 'Provider',
            'enabled' => 'Enabled',
        ],
        'drivers' => [
            'google' => 'Google',
            'apple' => 'Apple',
            'github' => 'GitHub',
            'oidc' => 'OpenID Connect',
        ],
        'create' => [
            'label' => 'Add provider',
        ],
        'delete' => [
            'label' => 'Delete provider',
            'confirm' => [
                'title' => 'Delete this provider?',
                'description' => 'Deletes the provider together with every account linked through it. Users who only sign in this way lose their way in.',
            ],
        ],
        'fields' => [
            'key' => [
                'label' => 'Key',
                'help-text' => 'Identifies the provider in its URLs and cannot be changed later. Its redirect URI is :url.',
            ],
            'driver' => [
                'label' => 'Provider',
            ],
            'callback-url' => [
                'label' => 'Redirect URI',
                'help-text' => 'Register this URI with the upstream provider.',
            ],
            'issuer' => [
                'label' => 'Issuer URL',
                'help-text' => 'The origin serving /.well-known/openid-configuration, e.g. https://login.example.com.',
            ],
            'client-id' => [
                'label' => 'Client ID',
                'help-text' => 'The client the upstream provider issued for this realm. For Apple it is the Services ID.',
            ],
            'client-secret' => [
                'label' => 'Client secret',
                'help-text' => 'The secret that goes with the client ID.',
            ],
            'team-id' => [
                'label' => 'Team ID',
                'help-text' => 'The Apple Developer team the Services ID belongs to.',
            ],
            'key-id' => [
                'label' => 'Key ID',
                'help-text' => 'The identifier of the Sign in with Apple private key.',
            ],
            'private-key' => [
                'label' => 'Private key',
                'help-text' => 'The contents of the .p8 key file Apple issued. Apple derives the client secret from it.',
            ],
            'secret' => [
                'help-text' => 'Stored encrypted and never shown again. Leave empty to keep the current one.',
            ],
            'enabled' => [
                'label' => 'Enabled',
                'help-text' => 'A disabled provider is closed rather than hidden: its sign-in URLs stop answering.',
            ],
        ],
    ],
    'events' => [
        'system-actor' => 'System (console)',
        'columns' => [
            'when' => 'When',
            'actor' => 'Actor',
            'type' => 'Event',
            'description' => 'Description',
            'ip' => 'IP address',
        ],
        'filters' => [
            'category' => 'Category',
            'type' => 'Event',
            'failure' => 'Failed',
        ],
        'detail' => [
            'empty' => 'No additional details recorded.',
            'client' => 'Client',
            'session' => 'Session',
            'ip' => 'IP address',
            'user-agent' => 'User agent',
        ],
    ],
    'admin-events' => [
        'heading' => 'Admin events',
        'empty' => 'No admin events yet.',
    ],
    'realm-admin-events' => [
        'heading' => 'Admin events',
        'description' => 'Configuration changes made to :realm.',
        'empty' => 'No admin events for this realm yet.',
    ],
    'realm-user-events' => [
        'heading' => 'User events',
        'description' => 'Sign-ins, multi-factor challenges and token activity in :realm.',
        'empty' => 'No user events recorded yet.',
    ],
    'api' => [
        'heading' => 'API',
        'description' => 'The management API of this instance. Execute a request straight from an operation to try it with your own permissions.',
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
            'description' => 'User of :realm.',
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
                'subtitle' => 'Name, email address, verification state and realm membership.',
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
                'help-text' => 'Add each absolute URI separately. Required for the authorization code grant.',
                'add' => 'Add redirect URI',
            ],
            'post-logout-redirect-uris' => [
                'label' => 'Post-logout redirect URIs',
                'help-text' => 'Add each absolute URI the client may use after logout separately.',
                'add' => 'Add post-logout URI',
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
            'secret' => [
                'heading' => 'Client secret',
                'subtitle' => 'Reveal the secret when you need to copy it. Rotate it if it leaks.',
                'reveal' => 'Reveal client secret',
                'label' => 'Client secret',
            ],
            'settings' => [
                'heading' => 'Settings',
                'subtitle' => 'Open a row to change that setting. Client ID, scopes and status are fixed.',
            ],
            'rotate-secret' => [
                'label' => 'Rotate secret',
                'done' => 'Client secret rotated.',
                'confirm' => [
                    'title' => 'Rotate the client secret?',
                    'description' => 'The current secret stops working immediately. You can reveal the new one on this page.',
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
    'resources' => [
        'heading' => 'Resources',
        'description' => 'Resource servers of :realm and the scopes they own.',
        'created' => 'Resource created.',
        'updated' => 'Resource updated.',
        'deleted' => 'Resource deleted.',
        'columns' => [
            'name' => 'Name',
            'identifier' => 'Identifier',
            'scopes' => 'Scopes',
        ],
        'create' => [
            'label' => 'Add resource',
        ],
        'edit' => [
            'label' => 'Edit resource',
        ],
        'delete' => [
            'label' => 'Delete resource',
            'confirm' => [
                'title' => 'Delete this resource?',
                'description' => 'Deletes the resource and every scope it owns. Tokens can no longer be requested for it.',
            ],
        ],
        'fields' => [
            'identifier' => [
                'label' => 'Identifier',
                'help-text' => 'A path below the realm issuer (api) is published as protected resource metadata; an absolute URI names a resource server hosted elsewhere.',
            ],
            'name' => [
                'label' => 'Name',
            ],
        ],
        'validation' => [
            'identifier-format' => 'The identifier must be a path segment such as api or v1/orders, or an absolute URI.',
            'identifier-uri' => 'The identifier must be an absolute URI with a host and no fragment.',
        ],
        'detail' => [
            'scopes' => ['heading' => 'Scopes'],
            'description' => 'Resource of :realm.',
            'audience' => 'Audience',
            'settings' => [
                'heading' => 'Resource',
                'subtitle' => 'The audience a client asks for with the resource parameter.',
            ],
        ],
        'scopes' => [
            'heading' => 'Scopes',
            'created' => 'Scope created.',
            'updated' => 'Scope updated.',
            'deleted' => 'Scope deleted.',
            'columns' => [
                'value' => 'Scope',
                'description' => 'Description',
            ],
            'create' => [
                'label' => 'Add scope',
            ],
            'edit' => [
                'label' => 'Edit scope',
            ],
            'delete' => [
                'label' => 'Delete scope',
                'confirm' => [
                    'title' => 'Delete this scope?',
                    'description' => 'Tokens can no longer be issued with it. Existing tokens keep it until they expire.',
                ],
            ],
            'fields' => [
                'value' => [
                    'label' => 'Scope',
                    'help-text' => 'The value a client requests. Unique within this resource; another resource may use the same one.',
                ],
                'description' => [
                    'label' => 'Description',
                    'help-text' => 'Shown on the consent screen. Without one the scope speaks for itself.',
                ],
            ],
            'validation' => [
                'value-format' => 'A scope may not contain spaces. Use letters, digits, dots, colons, slashes, dashes or underscores.',
            ],
        ],
    ],
    'realms' => [
        'heading' => 'Realms',
        'description' => 'Every realm is an isolated identity boundary with its own users, clients and authentication settings.',
        'columns' => [
            'name' => 'Name',
            'slug' => 'Slug',
            'domain' => 'Domain',
            'domain-status' => 'Domain status',
            'users' => 'Users',
            'created-at' => 'Created',
        ],
        'created' => 'Realm created.',
        'created-unverified' => 'Realm created. :outcome',
        'domain' => [
            'master-fixed' => 'The administration realm is served from APP_URL and takes no domain of its own.',
            'master' => 'Taken from APP_URL.',
            'status' => [
                'pending' => 'Not checked',
                'verified' => 'Verified',
                'unreachable' => 'Unreachable',
                'misrouted' => 'Wrong server',
            ],
            'outcome' => [
                'pending' => 'Not checked yet.',
                'verified' => ':domain reaches this instance.',
                'unreachable' => ':domain does not answer. Check its DNS record, the proxy entry and the TLS certificate.',
                'misrouted' => ':domain answers, but not as this Lock instance.',
            ],
            'checked-at' => 'Checked :time.',
            'change-warning' => 'The domain is the realm\'s issuer. Changing it signs every user out and every client, redirect URI and passkey of the realm has to follow.',
            'submit' => 'Save domain',
            'updated' => 'Domain changed.',
            'check' => 'Check again',
        ],
        'updated' => 'Realm updated.',
        'configuration-saved' => 'Realm configuration saved.',
        'deleted' => 'Realm deleted.',
        'settings' => [
            'heading' => 'Realm settings',
            'description' => 'Name, authentication configuration and deletion of :realm.',
        ],
        'tabs' => [
            'general' => 'General',
        ],
        'values' => [
            'never' => 'Never',
            'generated' => 'Generated per realm',
        ],
        'summary' => [
            'users' => 'Users',
            'created-at' => 'Created',
        ],
        'general' => [
            'heading' => 'Realm details',
            'subtitle' => 'Open a row to change it. The identifier is fixed for life.',
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
            'social' => 'Social sign-in',
            'general' => 'General',
            'login' => 'Sign-in',
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
        'login-methods' => [
            'password' => 'Email address and password',
            'passkey' => 'Passkey',
            'social' => 'Social provider',
        ],
        'mfa-requirements' => [
            'never' => 'Never',
            'if-enrolled' => 'When the user has a factor',
            'always' => 'Always',
        ],
        'fields' => [
            'name' => [
                'label' => 'Realm name',
            ],
            'slug' => [
                'label' => 'Realm identifier',
                'help-text' => 'Identifies the realm in the console. Lowercase letters, numbers and single hyphens only. Cannot be changed later.',
            ],
            'domain' => [
                'label' => 'Domain',
                'help-text' => 'The host the realm is served from, e.g. auth.example.com. Point its DNS at this server and add it to the platform proxy; the realm is created either way.',
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
            'login-methods' => [
                'label' => 'Accepted sign-in methods',
                'help-text' => 'Methods this realm offers. A method left out is closed rather than hidden, and registration as well as password reset hang off the password method.',
            ],
            'link-by-verified-email' => [
                'label' => 'Link by verified email address',
                'help-text' => 'Attach an upstream identity to the realm user with the same address, if the provider reports it as verified.',
            ],
            'auto-provision' => [
                'label' => 'Create users on first sign-in',
                'help-text' => 'Create a realm user the first time an unknown identity signs in through a provider. Only a verified upstream address is accepted.',
            ],
            'email-verification-required' => [
                'label' => 'Require a verified email address',
                'help-text' => 'Users with an unconfirmed address have to verify it before their sign-in completes.',
            ],
            'mfa-requirement' => [
                'label' => 'Second factor',
                'help-text' => 'When the realm challenges for a second factor. Always sends a user without one to enrollment before the sign-in completes.',
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
            'description' => 'Create a role and choose its scopes in :realm.',
        ],
        'detail' => [
            'description' => 'Role of :realm.',
            'created-at' => 'Created',
            'settings' => ['heading' => 'Settings'],
            'scopes' => ['empty' => 'No scopes assigned to this role.'],
            'protected' => [
                'label' => 'System role',
                'description' => 'This role is managed by Lock and cannot be changed or deleted.',
            ],
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
            'scopes' => [
                'label' => 'Scopes',
                'help-text' => 'What the role grants. A scope belongs to a resource of this realm; a role that may write also needs the matching read scope.',
            ],
        ],
        'validation' => [
            'name-format' => 'The name may only contain letters, digits, and the characters _ . : - and has to start with a letter or digit.',
        ],
    ],
];

<?php
declare(strict_types=1);

return [
    'fields' => [
        'name' => 'Tenant name',
    ],
    'api' => [
        'no-tenant-context' => 'This token is not bound to a tenant.',
    ],
    'permissions' => [
        'tenant' => [
            'update' => 'Update tenant settings',
            'delete' => 'Delete the tenant',
        ],
        'member' => [
            'view' => 'View tenant members and invitations',
            'manage' => 'Manage tenant members and invitations',
        ],
        'audit' => [
            'view' => 'View the tenant audit log',
        ],
        'billing' => [
            'manage' => 'Manage the tenant billing and subscription',
        ],
        'role' => [
            'manage' => 'Manage tenant roles',
        ],
    ],
    'roles' => [
        'owner' => 'Owner',
        'admin' => 'Admin',
        'member' => 'Member',
    ],
    'onboarding' => [
        'title' => 'Set up your workspace',
        'heading' => 'Create your workspace',
        'subtitle' => 'Every account works inside a workspace. Name yours to get started — you can invite others and create more later.',
    ],
    'create' => [
        'title' => 'Create a new tenant',
        'menu' => [
            'label' => 'Create new tenant',
        ],
        'submit' => 'Create tenant',
        'created' => 'Tenant created.',
        'name' => [
            'placeholder' => 'My tenant',
        ],
    ],
    'update' => [
        'submit' => 'Save tenant',
        'updated' => 'Tenant updated.',
    ],
    'delete' => [
        'warning' => 'Please proceed with caution, this cannot be undone.',
        'confirm-name' => 'Confirm tenant name',
        'name-mismatch' => 'The tenant name does not match.',
        'submit' => 'Delete tenant',
        'deleted' => 'Tenant deleted.',
    ],
    'invite' => [
        'submit' => 'Send invitation',
        'sent' => 'Invitation sent.',
        'email' => [
            'placeholder' => 'colleague@example.com',
        ],
        'already-member' => 'This email is already a member or has a pending invitation.',
    ],
    'switch' => [
        'label' => 'Switch tenant',
        'switched' => 'Switched to :tenant.',
    ],
    'members' => [
        'column' => 'Member',
        'actions' => [
            'label' => 'Member actions',
        ],
        'remove' => [
            'label' => 'Remove',
            'confirm' => [
                'title' => 'Remove member?',
                'description' => 'This user will lose access to the tenant.',
                'label' => 'Remove member',
            ],
        ],
        'owner-cannot-be-removed' => 'The tenant owner cannot be removed.',
        'owner-role-cannot-be-changed' => 'The tenant owner\'s role cannot be changed.',
        'removed' => 'Member removed.',
        'change-role' => 'Change role',
        'role-updated' => 'Member role updated.',
    ],
    'invitations' => [
        'column' => 'Invitation',
        'sent-column' => 'Sent',
        'actions' => [
            'label' => 'Invitation actions',
        ],
        'cancel' => [
            'label' => 'Cancel',
            'confirm' => [
                'title' => 'Cancel invitation?',
                'description' => 'The invitation link will stop working.',
                'label' => 'Cancel invitation',
            ],
        ],
        'cancelled' => 'Invitation cancelled.',
        'resend' => 'Resend',
        'resent' => 'Invitation resent.',
    ],
    'notification' => [
        'subject' => 'You\'ve been invited to join :tenant',
        'line' => ':inviter has invited you to join :tenant.',
        'action' => 'Accept invitation',
        'invitation' => [
            'title' => 'New tenant invitation',
            'body' => ':inviter invited you to join :tenant.',
        ],
        'removed' => [
            'title' => 'Removed from tenant',
            'body' => 'You were removed from :tenant.',
        ],
    ],
    'accept' => [
        'wrong-email' => 'This invitation was sent to a different email address.',
        'already-accepted' => 'This invitation has already been accepted.',
        'expired' => 'This invitation has expired.',
    ],
    'settings' => [
        'subtitle' => 'Manage tenant settings, members, and invitations.',
        'tabs' => [
            'general' => 'General',
            'members' => 'Members',
            'roles' => 'Roles',
            'api-tokens' => 'API Tokens',
            'billing' => 'Billing',
            'activity' => 'Activity',
            'danger' => 'Danger',
        ],
        'general' => [
            'heading' => 'Tenant details',
            'subtitle' => 'Update your tenant name.',
        ],
        'members' => [
            'heading' => 'Tenant members',
            'subtitle' => 'Manage who belongs to this tenant.',
            'invite' => [
                'heading' => 'Invite a member',
                'subtitle' => 'Send an invitation to add someone to this tenant.',
            ],
            'invitations' => [
                'heading' => 'Pending invitations',
                'subtitle' => 'Invitations that haven\'t been accepted yet.',
            ],
        ],
        'roles' => [
            'heading' => 'Roles',
            'subtitle' => 'Define custom roles and choose which permissions each one grants. Granting "Manage roles" lets that role edit any role\'s permissions, including its own — grant it carefully.',
            'create' => [
                'label' => 'Create role',
                'heading' => 'Create a role',
            ],
            'edit' => [
                'label' => 'Edit',
                'heading' => 'Edit role',
            ],
            'save' => 'Save role',
            'name' => [
                'placeholder' => 'e.g. Support',
            ],
            'permissions' => 'Permissions',
            'permissions-help' => 'Choose which actions this role is allowed to perform.',
            'column' => [
                'name' => 'Name',
                'members' => 'Members',
                'permissions' => 'Permissions',
            ],
            'actions' => [
                'label' => 'Role actions',
            ],
            'delete' => [
                'label' => 'Delete',
                'confirm' => [
                    'title' => 'Delete role?',
                    'description' => 'Members and invitations must not use this role.',
                    'label' => 'Delete role',
                ],
            ],
            'created' => 'Role created.',
            'updated' => 'Role updated.',
            'deleted' => 'Role deleted.',
            'in-use' => 'This role is assigned to members or pending invitations and cannot be deleted.',
        ],
        'api-tokens' => [
            'heading' => 'API tokens',
            'subtitle' => 'Create personal access tokens to use the API. Each token is limited to the scopes your role allows in this tenant.',
            'name' => [
                'label' => 'Token name',
                'placeholder' => 'e.g. CLI on my laptop',
            ],
            'scopes' => 'Scopes',
            'scopes-help' => 'Only scopes your role grants in this tenant will be issued.',
            'create' => 'Create token',
            'created' => 'API token created.',
            'reveal' => [
                'heading' => 'Your new token',
            ],
            'reveal-help' => 'Copy it now — it will not be shown again.',
            'columns' => [
                'name' => 'Name',
                'scopes' => 'Scopes',
                'created' => 'Created',
            ],
            'actions' => [
                'label' => 'Token actions',
            ],
            'revoke' => [
                'label' => 'Revoke',
                'confirm' => [
                    'title' => 'Revoke token?',
                    'description' => 'Applications using this token will immediately lose access.',
                    'label' => 'Revoke token',
                ],
            ],
            'revoked' => 'Token revoked.',
            'not-a-member' => 'You do not belong to this tenant.',
            'ungrantable-scopes' => 'Your role does not allow these scopes: :scopes',
            'detail' => [
                'empty' => 'This token has no scopes.',
            ],
        ],
        'activity' => [
            'heading' => 'Activity',
            'subtitle' => 'Audit trail of changes to this tenant.',
            'empty' => 'No activity yet.',
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
        'danger' => [
            'heading' => 'Delete tenant',
            'subtitle' => 'Permanently delete this tenant and all of its resources.',
        ],
    ],
];

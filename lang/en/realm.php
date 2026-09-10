<?php
declare(strict_types=1);

return [
    'fields' => [
        'name' => 'Realm name',
    ],
    'api' => [
        'no-realm-context' => 'This token is not bound to a realm.',
    ],
    'permissions' => [
        'realm' => [
            'update' => 'Update realm settings',
            'delete' => 'Delete the realm',
        ],
        'member' => [
            'view' => 'View realm members and invitations',
            'manage' => 'Manage realm members and invitations',
        ],
        'audit' => [
            'view' => 'View the realm audit log',
        ],
        'role' => [
            'manage' => 'Manage realm roles',
        ],
        'realm-settings' => [
            'manage' => 'Manage authentication configuration',
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
        'title' => 'Create a new realm',
        'menu' => [
            'label' => 'Create new realm',
        ],
        'submit' => 'Create realm',
        'created' => 'Realm created.',
        'name' => [
            'placeholder' => 'My realm',
        ],
    ],
    'update' => [
        'submit' => 'Save realm',
        'updated' => 'Realm updated.',
    ],
    'delete' => [
        'warning' => 'Please proceed with caution, this cannot be undone.',
        'confirm-name' => 'Confirm realm name',
        'name-mismatch' => 'The realm name does not match.',
        'submit' => 'Delete realm',
        'deleted' => 'Realm deleted.',
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
        'label' => 'Switch realm',
        'switched' => 'Switched to :realm.',
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
                'description' => 'This user will lose access to the realm.',
                'label' => 'Remove member',
            ],
        ],
        'owner-cannot-be-removed' => 'The realm owner cannot be removed.',
        'owner-role-cannot-be-changed' => 'The realm owner\'s role cannot be changed.',
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
        'subject' => 'You\'ve been invited to join :realm',
        'line' => ':inviter has invited you to join :realm.',
        'action' => 'Accept invitation',
        'invitation' => [
            'title' => 'New realm invitation',
            'body' => ':inviter invited you to join :realm.',
        ],
        'removed' => [
            'title' => 'Removed from realm',
            'body' => 'You were removed from :realm.',
        ],
    ],
    'accept' => [
        'wrong-email' => 'This invitation was sent to a different email address.',
        'already-accepted' => 'This invitation has already been accepted.',
        'expired' => 'This invitation has expired.',
    ],
    'settings' => [
        'subtitle' => 'Manage realm settings, members, and invitations.',
        'tabs' => [
            'general' => 'General',
            'members' => 'Members',
            'roles' => 'Roles',
            'activity' => 'Activity',
            'danger' => 'Danger',
            'configuration' => 'Configuration',
        ],
        'general' => [
            'heading' => 'Realm details',
            'subtitle' => 'Update your realm name.',
        ],
        'members' => [
            'heading' => 'Realm members',
            'subtitle' => 'Manage who belongs to this realm.',
            'invite' => [
                'heading' => 'Invite a member',
                'subtitle' => 'Send an invitation to add someone to this realm.',
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
        'activity' => [
            'heading' => 'Activity',
            'subtitle' => 'Audit trail of changes to this realm.',
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
            'heading' => 'Delete realm',
            'subtitle' => 'Permanently delete this realm and all of its resources.',
        ],
    ],
];

<?php

declare(strict_types=1);

namespace App\Tenants\Ui\Settings;

use App\Auth\Models\User;
use App\Tenants\Enums\TenantPermission;
use App\Tenants\Models\Tenant;
use App\Tenants\Ui\Forms\InviteTenantMemberForm;
use App\Tenants\Ui\Tables\TenantInvitationsTable;
use App\Tenants\Ui\Tables\TenantMembersTable;
use Lattice\Form\Components\Form;
use Lattice\Table\Components\Table;
use Lattice\Ui\Components\Card;
use Lattice\Ui\Components\Tab;

final class MembersTab
{
    public function __invoke(Tenant $tenant, User $user): Tab
    {
        return Tab::make('members', __('tenant.settings.tabs.members'))
            ->schema([
                Card::make(__('tenant.settings.members.invite.heading'), __('tenant.settings.members.invite.subtitle'))
                    ->schema([
                        Form::use(InviteTenantMemberForm::class),
                    ])
                    ->visible($user->can(TenantPermission::ManageMembers, $tenant)),
                Card::make(__('tenant.settings.members.heading'), __('tenant.settings.members.subtitle'))
                    ->schema([
                        // Stack columns cannot be searchable(), so the search box is
                        // enabled on the component; the table source applies the term.
                        Table::lazy(TenantMembersTable::class)->searchable(),
                    ]),
                Card::make(__('tenant.settings.members.invitations.heading'), __('tenant.settings.members.invitations.subtitle'))
                    ->schema([
                        Table::lazy(TenantInvitationsTable::class)->searchable(),
                    ]),
            ]);
    }
}

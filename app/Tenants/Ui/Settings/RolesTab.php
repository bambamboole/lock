<?php

declare(strict_types=1);

namespace App\Tenants\Ui\Settings;

use App\Auth\Models\User;
use App\Tenants\Enums\TenantPermission;
use App\Tenants\Models\Tenant;
use App\Tenants\Ui\Tables\TenantRolesTable;
use Lattice\Table\Components\Table;
use Lattice\Ui\Components\Card;
use Lattice\Ui\Components\Tab;

final class RolesTab
{
    public function __invoke(Tenant $tenant, User $user): Tab
    {
        return Tab::make('roles', __('tenant.settings.tabs.roles'))
            ->visible($user->can(TenantPermission::ManageRoles, $tenant))
            ->schema([
                Card::make(__('tenant.settings.roles.heading'), __('tenant.settings.roles.subtitle'))
                    ->schema([
                        Table::lazy(TenantRolesTable::class),
                    ]),
            ]);
    }
}

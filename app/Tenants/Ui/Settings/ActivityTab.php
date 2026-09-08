<?php

declare(strict_types=1);

namespace App\Tenants\Ui\Settings;

use App\Auth\Models\User;
use App\Tenants\Enums\TenantPermission;
use App\Tenants\Models\Tenant;
use App\Tenants\Ui\Tables\TenantActivityTable;
use Lattice\Table\Components\Table;
use Lattice\Ui\Components\Card;
use Lattice\Ui\Components\Tab;

final class ActivityTab
{
    public function __invoke(Tenant $tenant, User $user): Tab
    {
        return Tab::make('activity', __('tenant.settings.tabs.activity'))
            ->visible($user->can(TenantPermission::ViewAudit, $tenant))
            ->schema([
                Card::make(__('tenant.settings.activity.heading'), __('tenant.settings.activity.subtitle'))
                    ->schema([
                        Table::lazy(TenantActivityTable::class),
                    ]),
            ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Tenants\Ui\Settings;

use App\Auth\Models\User;
use App\Tenants\Enums\TenantPermission;
use App\Tenants\Models\Tenant;
use App\Tenants\Ui\Forms\UpdateTenantForm;
use Lattice\Form\Components\Form;
use Lattice\Ui\Components\Card;
use Lattice\Ui\Components\Tab;

final class GeneralTab
{
    public function __invoke(Tenant $tenant, User $user): Tab
    {
        return Tab::make('general', __('tenant.settings.tabs.general'))
            ->visible($user->can(TenantPermission::UpdateTenant, $tenant))
            ->schema([
                Card::make(__('tenant.settings.general.heading'), __('tenant.settings.general.subtitle'))
                    ->schema([
                        Form::use(UpdateTenantForm::class),
                    ]),
            ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Tenants\Ui\Settings;

use App\Auth\Models\User;
use App\Tenants\Models\Tenant;
use App\Tenants\Ui\Forms\DeleteTenantForm;
use Lattice\Form\Components\Form;
use Lattice\Ui\Components\Card;
use Lattice\Ui\Components\Tab;

final class DangerTab
{
    public function __invoke(Tenant $tenant, User $user): Tab
    {
        return Tab::make('danger', __('tenant.settings.tabs.danger'))
            ->visible($user->can('delete', $tenant))
            ->schema([
                Card::make(__('tenant.settings.danger.heading'), __('tenant.settings.danger.subtitle'))
                    ->schema([
                        Form::use(DeleteTenantForm::class),
                    ]),
            ]);
    }
}

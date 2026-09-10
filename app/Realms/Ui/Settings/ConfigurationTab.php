<?php

declare(strict_types=1);

namespace App\Realms\Ui\Settings;

use App\Auth\Models\User;
use App\Realms\Enums\RealmPermission;
use App\Realms\Models\Realm;
use App\Realms\Ui\Forms\UpdateRealmConfigurationForm;
use Lattice\Form\Components\Form;
use Lattice\Ui\Components\Tab;

final class ConfigurationTab
{
    public function __invoke(Realm $realm, User $user): Tab
    {
        return Tab::make('configuration', __('realm.settings.tabs.configuration'))
            ->visible($user->can(RealmPermission::ManageSettings, $realm))
            ->schema([
                Form::use(UpdateRealmConfigurationForm::class),
            ]);
    }
}

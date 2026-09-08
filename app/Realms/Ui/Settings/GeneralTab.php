<?php

declare(strict_types=1);

namespace App\Realms\Ui\Settings;

use App\Auth\Models\User;
use App\Realms\Enums\RealmPermission;
use App\Realms\Models\Realm;
use App\Realms\Ui\Forms\UpdateRealmForm;
use Lattice\Form\Components\Form;
use Lattice\Ui\Components\Card;
use Lattice\Ui\Components\Tab;

final class GeneralTab
{
    public function __invoke(Realm $realm, User $user): Tab
    {
        return Tab::make('general', __('realm.settings.tabs.general'))
            ->visible($user->can(RealmPermission::UpdateRealm, $realm))
            ->schema([
                Card::make(__('realm.settings.general.heading'), __('realm.settings.general.subtitle'))
                    ->schema([
                        Form::use(UpdateRealmForm::class),
                    ]),
            ]);
    }
}

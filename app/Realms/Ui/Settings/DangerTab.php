<?php

declare(strict_types=1);

namespace App\Realms\Ui\Settings;

use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Realms\Ui\Forms\DeleteRealmForm;
use Lattice\Form\Components\Form;
use Lattice\Ui\Components\Card;
use Lattice\Ui\Components\Tab;

final class DangerTab
{
    public function __invoke(Realm $realm, User $user): Tab
    {
        return Tab::make('danger', __('realm.settings.tabs.danger'))
            ->visible($user->can('delete', $realm))
            ->schema([
                Card::make(__('realm.settings.danger.heading'), __('realm.settings.danger.subtitle'))
                    ->schema([
                        Form::use(DeleteRealmForm::class),
                    ]),
            ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Realms\Ui\Settings;

use App\Auth\Models\User;
use App\Realms\Enums\RealmPermission;
use App\Realms\Models\Realm;
use App\Realms\Ui\Tables\RealmRolesTable;
use Lattice\Table\Components\Table;
use Lattice\Ui\Components\Card;
use Lattice\Ui\Components\Tab;

final class RolesTab
{
    public function __invoke(Realm $realm, User $user): Tab
    {
        return Tab::make('roles', __('realm.settings.tabs.roles'))
            ->visible($user->can(RealmPermission::ManageRoles, $realm))
            ->schema([
                Card::make(__('realm.settings.roles.heading'), __('realm.settings.roles.subtitle'))
                    ->schema([
                        Table::lazy(RealmRolesTable::class),
                    ]),
            ]);
    }
}

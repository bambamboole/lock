<?php

declare(strict_types=1);

namespace App\Realms\Ui\Settings;

use App\Auth\Models\User;
use App\Realms\Enums\RealmPermission;
use App\Realms\Models\Realm;
use App\Realms\Ui\Tables\RealmActivityTable;
use Lattice\Table\Components\Table;
use Lattice\Ui\Components\Card;
use Lattice\Ui\Components\Tab;

final class ActivityTab
{
    public function __invoke(Realm $realm, User $user): Tab
    {
        return Tab::make('activity', __('realm.settings.tabs.activity'))
            ->visible($user->can(RealmPermission::ViewAudit, $realm))
            ->schema([
                Card::make(__('realm.settings.activity.heading'), __('realm.settings.activity.subtitle'))
                    ->schema([
                        Table::lazy(RealmActivityTable::class),
                    ]),
            ]);
    }
}

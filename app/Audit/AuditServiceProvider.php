<?php

declare(strict_types=1);

namespace App\Audit;

use App\Audit\Ui\Pages\ActivityPage;
use App\Audit\Ui\Pages\RealmActivityPage;
use App\Audit\Ui\Pages\RealmEventsPage;
use App\Realms\Models\Realm;
use App\Shared\Authorization\Enums\Scope;
use Illuminate\Support\ServiceProvider;
use Lattice\Core\Facades\Lattice;
use Lattice\Core\Support\Affix;
use Lattice\Ui\Components\MenuItem;
use Lattice\Ui\Enums\Icon;

final class AuditServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Lattice::extend(
            'admin.sidebar.realm',
            static fn (Realm $realm): MenuItem => MenuItem::fromPage(RealmEventsPage::class, ['realm' => $realm->slug])
                ->label(__('navigation.events'))
                ->prefix(Affix::icon('shield-alert'))
                ->can(Scope::ActivityRead),
            priority: 50,
        );

        Lattice::extend(
            'admin.sidebar.realm',
            static fn (Realm $realm): MenuItem => MenuItem::fromPage(RealmActivityPage::class, ['realm' => $realm->slug])
                ->label(__('navigation.activity'))
                ->prefix(Icon::Clock)
                ->can(Scope::ActivityRead),
            priority: 60,
        );

        Lattice::extend(
            'admin.sidebar.instance',
            static fn (): MenuItem => MenuItem::fromPage(ActivityPage::class)
                ->label(__('navigation.activity'))
                ->prefix(Icon::Clock)
                ->can(Scope::ActivityRead),
            priority: 30,
        );
    }
}

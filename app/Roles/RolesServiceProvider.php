<?php

declare(strict_types=1);

namespace App\Roles;

use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Roles\Actions\CreateRole;
use App\Roles\Actions\DeleteRole;
use App\Roles\Actions\SyncUserRoles;
use App\Roles\Actions\UpdateRole;
use App\Roles\Models\Role;
use App\Roles\Ui\Components\UserRolesCard;
use App\Roles\Ui\Pages\RolesPage;
use App\Shared\Api\Enums\ApiScope;
use App\Shared\Roles\Contracts\CreatesRole;
use App\Shared\Roles\Contracts\DeletesRole;
use App\Shared\Roles\Contracts\SyncsUserRoles;
use App\Shared\Roles\Contracts\UpdatesRole;
use Illuminate\Support\ServiceProvider;
use Lattice\Core\Facades\Lattice;
use Lattice\Core\Support\Affix;
use Lattice\Ui\Components\Card;
use Lattice\Ui\Components\MenuItem;

final class RolesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CreatesRole::class, CreateRole::class);
        $this->app->bind(UpdatesRole::class, UpdateRole::class);
        $this->app->bind(DeletesRole::class, DeleteRole::class);
        $this->app->bind(SyncsUserRoles::class, SyncUserRoles::class);
    }

    public function boot(): void
    {
        Lattice::extend(
            'admin.sidebar.realm',
            static fn (Realm $realm): MenuItem => MenuItem::fromPage(RolesPage::class, ['realm' => $realm->slug])
                ->label(__('navigation.roles'))
                ->prefix(Affix::icon('shield-check'))
                ->can(ApiScope::RolesRead),
            priority: 30,
        );

        Lattice::context('role', fn (string $value): Role => Role::query()->findOrFail($value));

        Lattice::extend(
            'admin.users.detail.cards',
            static fn (User $target): Card => UserRolesCard::make($target),
        );
    }
}

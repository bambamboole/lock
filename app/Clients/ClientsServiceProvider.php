<?php

declare(strict_types=1);

namespace App\Clients;

use App\Clients\Actions\CreateClient;
use App\Clients\Actions\DeleteClient;
use App\Clients\Actions\ProvisionConsoleClient;
use App\Clients\Actions\RotateClientSecret;
use App\Clients\Actions\SetClientRevocation;
use App\Clients\Actions\UpdateClient;
use App\Clients\Ui\Pages\RealmClientsPage;
use App\Realms\Models\Realm;
use App\Shared\Authorization\Enums\Scope;
use App\Shared\Clients\Contracts\CreatesClient;
use App\Shared\Clients\Contracts\DeletesClient;
use App\Shared\Clients\Contracts\ProvisionsConsoleClient;
use App\Shared\Clients\Contracts\RotatesClientSecret;
use App\Shared\Clients\Contracts\SetsClientRevocation;
use App\Shared\Clients\Contracts\UpdatesClient;
use Illuminate\Support\ServiceProvider;
use Lattice\Core\Facades\Lattice;
use Lattice\Core\Support\Affix;
use Lattice\Ui\Components\MenuItem;
use Lock\Server\Clients\Models\Client;

final class ClientsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CreatesClient::class, CreateClient::class);
        $this->app->bind(ProvisionsConsoleClient::class, ProvisionConsoleClient::class);
        $this->app->bind(UpdatesClient::class, UpdateClient::class);
        $this->app->bind(RotatesClientSecret::class, RotateClientSecret::class);
        $this->app->bind(SetsClientRevocation::class, SetClientRevocation::class);
        $this->app->bind(DeletesClient::class, DeleteClient::class);
    }

    public function boot(): void
    {
        Lattice::extend(
            'admin.sidebar.realm',
            static fn (Realm $realm): MenuItem => MenuItem::fromPage(RealmClientsPage::class, ['realm' => $realm->slug])
                ->label(__('navigation.clients'))
                ->prefix(Affix::icon('key-round'))
                ->can(Scope::ClientsRead),
            priority: 20,
        );

        Lattice::context('client', fn (string $value): Client => Client::query()->findOrFail($value));
    }
}

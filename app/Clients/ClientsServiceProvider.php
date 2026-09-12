<?php

declare(strict_types=1);

namespace App\Clients;

use App\Clients\Actions\CreateClient;
use App\Clients\Actions\DeleteClient;
use App\Clients\Actions\ProvisionConsoleClient;
use App\Clients\Actions\RotateClientSecret;
use App\Clients\Actions\SetClientRevocation;
use App\Clients\Actions\UpdateClient;
use App\Shared\Clients\Contracts\CreatesClient;
use App\Shared\Clients\Contracts\DeletesClient;
use App\Shared\Clients\Contracts\ProvisionsConsoleClient;
use App\Shared\Clients\Contracts\RotatesClientSecret;
use App\Shared\Clients\Contracts\SetsClientRevocation;
use App\Shared\Clients\Contracts\UpdatesClient;
use Bambamboole\LaravelOidc\Server\Clients\Models\Client;
use Illuminate\Support\ServiceProvider;
use Lattice\Core\Facades\Lattice;

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
        Lattice::context('client', fn (string $value): Client => Client::query()->findOrFail($value));
    }
}

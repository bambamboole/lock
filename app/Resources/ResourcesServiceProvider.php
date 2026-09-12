<?php

declare(strict_types=1);

namespace App\Resources;

use App\Resources\Actions\CreateResource;
use App\Resources\Actions\CreateResourceScope;
use App\Resources\Actions\DeleteResource;
use App\Resources\Actions\DeleteResourceScope;
use App\Resources\Actions\UpdateResource;
use App\Resources\Actions\UpdateResourceScope;
use App\Resources\Models\Resource;
use App\Resources\Models\ResourceScope;
use App\Resources\Support\RealmResources;
use App\Shared\Resources\Contracts\CreatesResource;
use App\Shared\Resources\Contracts\CreatesResourceScope;
use App\Shared\Resources\Contracts\DeletesResource;
use App\Shared\Resources\Contracts\DeletesResourceScope;
use App\Shared\Resources\Contracts\ProvidesRealmResources;
use App\Shared\Resources\Contracts\UpdatesResource;
use App\Shared\Resources\Contracts\UpdatesResourceScope;
use Illuminate\Support\ServiceProvider;
use Lattice\Core\Facades\Lattice;

final class ResourcesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(RealmResources::class);
        $this->app->bind(ProvidesRealmResources::class, RealmResources::class);
        $this->app->bind(CreatesResource::class, CreateResource::class);
        $this->app->bind(UpdatesResource::class, UpdateResource::class);
        $this->app->bind(DeletesResource::class, DeleteResource::class);
        $this->app->bind(CreatesResourceScope::class, CreateResourceScope::class);
        $this->app->bind(UpdatesResourceScope::class, UpdateResourceScope::class);
        $this->app->bind(DeletesResourceScope::class, DeleteResourceScope::class);
    }

    public function boot(): void
    {
        Lattice::context('resource', fn (string $value): Resource => Resource::query()->findOrFail($value));
        Lattice::context('resourceScope', fn (string $value): ResourceScope => ResourceScope::query()->findOrFail($value));
    }
}

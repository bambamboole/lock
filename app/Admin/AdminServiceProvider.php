<?php

declare(strict_types=1);

namespace App\Admin;

use App\Admin\Enums\AdminPermission;
use App\Realms\Models\Realm;
use Bambamboole\LaravelOidc\Server\Clients\Models\Client;
use Illuminate\Support\ServiceProvider;
use Lattice\Core\Facades\Lattice;
use Lattice\Ui\Components\MenuItem;
use Lattice\Ui\Enums\Icon;

final class AdminServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /*
         * Lattice round-trips the realm by slug on lazy table and form
         * requests. Every realm definition gates itself on an admin
         * permission, so resolving is a plain lookup.
         */
        Lattice::context(
            'realm',
            fn (string $value): Realm => Realm::where('slug', $value)->firstOrFail(),
            keyBy: fn (Realm $realm): string => $realm->slug,
        );

        Lattice::context('client', fn (string $value): Client => Client::query()->findOrFail($value));

        Lattice::extend('app.user-menu', static fn (): MenuItem => MenuItem::make(__('navigation.admin'))
            ->href(route('admin.realms', absolute: false))
            ->prefix(Icon::Settings)
            ->can(AdminPermission::Access));
    }
}

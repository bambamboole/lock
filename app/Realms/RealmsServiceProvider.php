<?php

declare(strict_types=1);

namespace App\Realms;

use App\Auth\Models\User;
use App\Realms\Actions\CreateRealm;
use App\Realms\Models\Realm;
use App\Realms\Models\RealmInvitation;
use App\Realms\Models\Role;
use App\Realms\Support\RealmConfiguration;
use App\Realms\Support\RealmMemberRoles;
use App\Realms\Ui\Settings\ActivityTab;
use App\Realms\Ui\Settings\ConfigurationTab;
use App\Realms\Ui\Settings\DangerTab;
use App\Realms\Ui\Settings\GeneralTab;
use App\Realms\Ui\Settings\MembersTab;
use App\Realms\Ui\Settings\RolesTab;
use App\Shared\Audit\Models\Activity;
use App\Shared\Realms\Contracts\CreatesRealm;
use App\Shared\Realms\Contracts\RealmMemberDirectory;
use App\Shared\Settings\SettingsRegistry;
use Illuminate\Support\ServiceProvider;
use Lattice\Core\Facades\Lattice;
use Lattice\Core\Services\ContextResolutions;

final class RealmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RealmMemberDirectory::class, RealmMemberRoles::class);
        $this->app->bind(CreatesRealm::class, CreateRealm::class);
    }

    public function boot(): void
    {
        RealmConfiguration::register($this->app->make(SettingsRegistry::class));
        /*
         * Lattice round-trips the realm by slug on lazy table and form
         * requests, so the slug arrives from the client. Resolving it without
         * checking the signed-in user would let a request name any realm and
         * read its members, roles and audit trail.
         */
        Lattice::context(
            'realm',
            function (string $value): Realm {
                $realm = Realm::where('slug', $value)->firstOrFail();
                $user = auth()->user();

                abort_unless($user instanceof User && $user->canAccessRealm($realm), 404);

                return $realm;
            },
            keyBy: fn (Realm $realm): string => $realm->slug,
        );

        Lattice::context('member', function (string $value, array $context, ContextResolutions $resolutions): User {
            $realm = $resolutions->resolve('realm', $context['realm'] ?? null, $context);
            abort_unless($realm instanceof Realm, 404);

            return $realm->users()->findOrFail($value);
        }, keyBy: fn (User $member): string => $member->id);

        Lattice::context('invitation', function (string $value, array $context, ContextResolutions $resolutions): RealmInvitation {
            $realm = $resolutions->resolve('realm', $context['realm'] ?? null, $context);
            abort_unless($realm instanceof Realm, 404);

            return $realm->invitations()->where('code', $value)->firstOrFail();
        }, keyBy: fn (RealmInvitation $invitation): string => $invitation->code);

        Lattice::context('role', function (string $value, array $context, ContextResolutions $resolutions): Role {
            $realm = $resolutions->resolve('realm', $context['realm'] ?? null, $context);
            abort_unless($realm instanceof Realm, 404);

            return $realm->roles()->findOrFail($value);
        }, keyBy: fn (Role $role): string => $role->id);

        Lattice::context('activity', function (string|int $value, array $context, ContextResolutions $resolutions): Activity {
            $realm = $resolutions->resolve('realm', $context['realm'] ?? null, $context);
            abort_unless($realm instanceof Realm, 404);

            return Activity::query()->forRealm($realm)->findOrFail($value);
        }, keyBy: fn (Activity $activity): int => (int) $activity->getKey());

        Lattice::extend('realm.settings.tabs', (new GeneralTab)(...), priority: 10);
        Lattice::extend('realm.settings.tabs', (new ConfigurationTab)(...), priority: 30);
        Lattice::extend('realm.settings.tabs', (new MembersTab)(...), priority: 20);
        Lattice::extend('realm.settings.tabs', (new RolesTab)(...), priority: 25);
        Lattice::extend('realm.settings.tabs', (new ActivityTab)(...), priority: 40);
        Lattice::extend('realm.settings.tabs', (new DangerTab)(...), priority: 100);
    }
}

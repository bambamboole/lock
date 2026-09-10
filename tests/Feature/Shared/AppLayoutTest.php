<?php

declare(strict_types=1);

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Shared\Ui\Layouts\AppLayout;
use Lattice\Core\Facades\Lattice;
use Lattice\Support\Testing\ComponentNode;
use Lattice\Ui\Components\MenuItem;
use Lattice\Ui\PageSchema;

use function Tests\Helpers\grantGlobalRole;
use function Tests\Helpers\menuItemHrefs;

function appLayoutSchema(User $user): ComponentNode
{
    auth()->setUser($user);

    $request = request();
    $request->setUserResolver(fn (): User => $user);

    $renderable = (new AppLayout)->schema(PageSchema::make(), $request)->renderable();
    $schema = json_decode(json_encode($renderable, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);

    return ComponentNode::root($schema);
}

/**
 * @return array<int, mixed>
 */
function userMenuHrefs(User $user): array
{
    return menuItemHrefs(appLayoutSchema($user)->firstOfTypeOrFail('dropdown', 'user-menu'));
}

it('adds an admin entry to the user menu only for admins, leading to the area they may manage', function (string $roleName, string $entry) {
    (new GlobalRoleSeeder)->run();

    $user = User::factory()->for(Realm::master())->create();
    $hrefsBefore = userMenuHrefs($user);

    grantGlobalRole($user, $roleName);
    $hrefsAfter = userMenuHrefs($user->refresh());

    expect($hrefsBefore)->toBe(['/profile', '/logout'])
        ->and($hrefsAfter)->toBe(['/profile', $entry, '/logout']);
})->with([
    'super admin' => [GlobalRoleSeeder::SUPER_ADMIN, '/admin/realms'],
    'support admin' => [GlobalRoleSeeder::SUPPORT, '/admin/users'],
]);

it('lets a module add its own sidebar entry through the app.navigation slot', function () {
    $user = User::factory()->create();
    $sidebarHrefs = fn (): array => menuItemHrefs(appLayoutSchema($user)->firstOfTypeOrFail('menu', 'sidebar'));

    $before = $sidebarHrefs();

    Lattice::extend('app.navigation', static fn (): MenuItem => MenuItem::make('Reports')->href('/reports'));

    expect($sidebarHrefs())->toBe([...$before, '/reports']);
});

it('lets a module add its own entry to the user menu through the app.user-menu slot', function () {
    $user = User::factory()->create();

    Lattice::extend('app.user-menu', static fn (): MenuItem => MenuItem::make('Reports')->href('/reports'));

    expect(userMenuHrefs($user))->toBe(['/profile', '/reports', '/logout']);
});

it('names the realm once per breakpoint, under distinct keys', function () {
    $user = User::factory()->create();

    $labels = appLayoutSchema($user)
        ->findAll(fn (ComponentNode $node): bool => in_array($node->key(), ['realm-label', 'realm-label-mobile'], true));

    $byKey = collect($labels)
        ->mapWithKeys(fn (ComponentNode $node): array => [
            (string) $node->key() => [$node->prop('visibleFrom'), $node->prop('hiddenFrom')],
        ]);

    expect($byKey->get('realm-label'))->toBe(['md', null])
        ->and($byKey->get('realm-label-mobile'))->toBe([null, 'md']);
});

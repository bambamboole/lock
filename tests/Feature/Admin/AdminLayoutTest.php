<?php

declare(strict_types=1);

use App\Realms\Models\Realm;
use App\Shared\Api\ManagementRoles;
use App\Shared\Authorization\Enums\Scope;
use Lattice\Support\Testing\ComponentNode;
use Pest\Expectation;

use function Tests\Helpers\globalAdmin;
use function Tests\Helpers\globalAdminWith;
use function Tests\Helpers\latticeSchema;
use function Tests\Helpers\menuItemHrefs;

it('keeps the instance areas below the realm the sidebar is showing', function () {
    $response = $this->actingAs(globalAdmin())->get(route('admin.realms'))->assertOk();
    $layout = latticeSchema($response, 'props.lattice.layout.schema');

    expect(menuItemHrefs($layout->firstOfTypeOrFail('menu', 'admin-sidebar-instance-menu')))
        ->toBe(['/admin/realms', '/admin/events', '/admin/api']);
});

it('drops the instance areas an admin may not open', function () {
    $response = $this->actingAs(globalAdminWith(Scope::UsersRead))->get(route('admin.realms.users', ['realm' => 'admin']))->assertOk();

    expect(latticeSchema($response, 'props.lattice.layout.schema')->firstOfType('menu', 'admin-sidebar-instance-menu'))->toBeNull();
});

it('shows a realm console on the pages that name no realm, following the realm last opened', function () {
    Realm::factory()->create(['name' => 'Acme', 'slug' => 'acme']);

    $this->actingAs(globalAdmin());

    $onMaster = $this->get(route('admin.api'))->assertOk();

    expect(menuItemHrefs(latticeSchema($onMaster, 'props.lattice.layout.schema')->firstOfTypeOrFail('menu', 'admin-sidebar-menu')))
        ->each(fn (Expectation $href): Expectation => $href->toStartWith('/admin/realms/admin/'));

    $this->get(route('admin.realms.users', ['realm' => 'acme']))->assertOk();
    $onAcme = $this->get(route('admin.api'))->assertOk();

    expect(menuItemHrefs(latticeSchema($onAcme, 'props.lattice.layout.schema')->firstOfTypeOrFail('menu', 'admin-sidebar-menu')))
        ->each(fn (Expectation $href): Expectation => $href->toStartWith('/admin/realms/acme/'));
});

it('turns the sidebar into the selected realm console, showing only what the admin may open', function (string $roleName, array $areas) {
    Realm::factory()->create(['name' => 'Acme', 'slug' => 'acme']);

    $response = $this->actingAs(globalAdmin($roleName))->get(route('admin.realms.settings', ['realm' => 'acme']))->assertOk();

    expect(menuItemHrefs(latticeSchema($response, 'props.lattice.layout.schema')->firstOfTypeOrFail('menu', 'admin-sidebar-menu')))
        ->toBe(array_map(fn (string $area): string => "/admin/realms/acme/{$area}", $areas));
})->with([
    'super admin' => [ManagementRoles::SUPER_ADMIN, ['users', 'clients', 'roles', 'resources', 'user-events', 'admin-events', 'settings']],
    'support admin' => [ManagementRoles::SUPPORT, ['users', 'roles', 'user-events', 'settings']],
]);

it('lists every realm in the switcher and marks the selected one', function () {
    Realm::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
    Realm::factory()->create(['name' => 'Globex', 'slug' => 'globex']);

    $response = $this->actingAs(globalAdmin())->get(route('admin.realms.users', ['realm' => 'globex']))->assertOk();
    $switcher = latticeSchema($response, 'props.lattice.layout.schema')->firstOfTypeOrFail('dropdown', 'realm-switcher');
    $items = $switcher->findAll(fn (ComponentNode $node): bool => $node->type() === 'menu-item');

    expect(menuItemHrefs($switcher))->toBe(['/admin/realms/acme/users', '/admin/realms/admin/users', '/admin/realms/globex/users', '/admin/realms'])
        ->and(array_map(fn (ComponentNode $item): bool => str_contains(json_encode($item->prop('prefix')) ?: '', '"check"'), $items))->toBe([false, false, true, false]);
});

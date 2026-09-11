<?php

declare(strict_types=1);

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Admin\Support\GlobalRoles;
use App\Realms\Models\Realm;
use Lattice\Support\Testing\ComponentNode;

use function Tests\Helpers\globalAdmin;
use function Tests\Helpers\latticeSchema;
use function Tests\Helpers\menuItemHrefs;

beforeEach(function () {
    (new GlobalRoleSeeder)->run();
});

it('shows the instance areas while no realm is selected', function () {
    $response = $this->actingAs(globalAdmin())->get(route('admin.realms'))->assertOk();

    expect(menuItemHrefs(latticeSchema($response, 'props.lattice.layout.schema')->firstOfTypeOrFail('menu', 'admin-sidebar-menu')))
        ->toBe(['/admin/realms', '/admin/activity']);
});

it('turns the sidebar into the selected realm console, showing only what the admin may open', function (string $roleName, array $areas) {
    Realm::factory()->create(['name' => 'Acme', 'slug' => 'acme']);

    $response = $this->actingAs(globalAdmin($roleName))->get(route('admin.realms.settings', ['realm' => 'acme']))->assertOk();

    expect(menuItemHrefs(latticeSchema($response, 'props.lattice.layout.schema')->firstOfTypeOrFail('menu', 'admin-sidebar-menu')))
        ->toBe(array_map(fn (string $area): string => "/admin/realms/acme/{$area}", $areas));
})->with([
    'super admin' => [GlobalRoles::SUPER_ADMIN, ['users', 'clients', 'roles', 'resources', 'events', 'activity', 'settings']],
    'support admin' => [GlobalRoles::SUPPORT, ['users', 'roles', 'events', 'activity', 'settings']],
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

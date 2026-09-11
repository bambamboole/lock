<?php

declare(strict_types=1);

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Admin\Support\GlobalRoles;
use App\Realms\Models\Realm;
use Illuminate\Http\Response;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Lattice\Support\Testing\ComponentNode;

use function Tests\Helpers\globalAdmin;
use function Tests\Helpers\menuItemHrefs;

beforeEach(function () {
    (new GlobalRoleSeeder)->run();
});

/**
 * @param  TestResponse<Response>  $response
 */
function adminLayout(TestResponse $response): ComponentNode
{
    $layout = data_get(AssertableInertia::fromTestResponse($response)->toArray(), 'props.lattice.layout.schema');

    return ComponentNode::root(is_array($layout) ? $layout : []);
}

it('shows the instance areas while no realm is selected', function (string $roleName) {
    $response = $this->actingAs(globalAdmin($roleName))->get(route('admin.realms'))->assertOk();

    expect(menuItemHrefs(adminLayout($response)->firstOfTypeOrFail('menu', 'admin-sidebar-menu')))
        ->toBe(['/admin/realms', '/admin/activity']);
})->with(['support admin' => GlobalRoles::SUPPORT, 'super admin' => GlobalRoles::SUPER_ADMIN]);

it('turns the sidebar into the selected realm console', function () {
    $realm = Realm::factory()->create(['name' => 'Acme', 'slug' => 'acme']);

    $response = $this->actingAs(globalAdmin())->get(route('admin.realms.settings', ['realm' => 'acme']))->assertOk();

    expect(menuItemHrefs(adminLayout($response)->firstOfTypeOrFail('menu', 'admin-sidebar-menu')))
        ->toBe([
            '/admin/realms/acme/users',
            '/admin/realms/acme/clients',
            '/admin/realms/acme/roles',
            '/admin/realms/acme/resources',
            '/admin/realms/acme/events',
            '/admin/realms/acme/activity',
            '/admin/realms/acme/settings',
        ]);
});

it('lists every realm in the switcher and marks the selected one', function () {
    Realm::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
    Realm::factory()->create(['name' => 'Globex', 'slug' => 'globex']);

    $response = $this->actingAs(globalAdmin())->get(route('admin.realms.users', ['realm' => 'globex']))->assertOk();
    $switcher = adminLayout($response)->firstOfTypeOrFail('dropdown', 'realm-switcher');
    $items = $switcher->findAll(fn (ComponentNode $node): bool => $node->type() === 'menu-item');

    expect(menuItemHrefs($switcher))->toBe(['/admin/realms/acme/users', '/admin/realms/admin/users', '/admin/realms/globex/users', '/admin/realms'])
        ->and(array_map(fn (ComponentNode $item): bool => str_contains(json_encode($item->prop('prefix')) ?: '', '"check"'), $items))->toBe([false, false, true, false]);
});

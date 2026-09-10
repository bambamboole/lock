<?php

declare(strict_types=1);

use App\Admin\Database\Seeders\GlobalRoleSeeder;
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
 * @return array<int, mixed>
 */
function adminSidebarHrefs(TestResponse $response): array
{
    $layout = data_get(AssertableInertia::fromTestResponse($response)->toArray(), 'props.lattice.layout.schema');

    return menuItemHrefs(ComponentNode::root(is_array($layout) ? $layout : [])
        ->firstOfTypeOrFail('menu', 'admin-sidebar-menu'));
}

it('scopes the admin sidebar to the areas the admin may manage', function (string $roleName, array $hrefs) {
    $response = $this->actingAs(globalAdmin($roleName))->get(route('admin.users'))->assertOk();

    expect(adminSidebarHrefs($response))->toBe($hrefs);
})->with([
    'support admin' => [GlobalRoleSeeder::SUPPORT, ['/admin/users', '/admin/activity']],
    'super admin' => [GlobalRoleSeeder::SUPER_ADMIN, ['/admin/users', '/admin/realms', '/admin/activity']],
]);

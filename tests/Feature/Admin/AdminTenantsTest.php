<?php

declare(strict_types=1);

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Admin\Ui\Tables\AdminTenantsTable;
use App\Auth\Models\User;
use App\Tenants\Enums\RolePreset;
use App\Tenants\Models\Tenant;
use Illuminate\Http\Response;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Lattice\Support\Testing\ComponentNode;

use function Tests\Helpers\addMember;
use function Tests\Helpers\assumeNotNull;
use function Tests\Helpers\globalAdmin;
use function Tests\Helpers\tenantMember;

beforeEach(function () {
    (new GlobalRoleSeeder)->run();
});

/**
 * The label/value pairs of every description-list entry inside the card titled
 * $title. An entry without a value falls back to its placeholder, which is what
 * the reader actually sees.
 *
 * @param  TestResponse<Response>  $response
 * @return array<int, array<int, string>>
 */
function detailRowsOf(TestResponse $response, string $title): array
{
    $schema = data_get(AssertableInertia::fromTestResponse($response)->toArray(), 'props.lattice.schema');

    $card = ComponentNode::root(is_array($schema) ? $schema : [])
        ->findOrFail(
            fn (ComponentNode $node): bool => $node->type() === 'card' && $node->prop('title') === $title,
            "card titled [{$title}]",
        );

    return collect($card->findAll(
        fn (ComponentNode $node): bool => str_starts_with((string) $node->type(), 'entry.'),
    ))
        ->map(fn (ComponentNode $entry): array => [
            (string) $entry->prop('label'),
            (string) ($entry->prop('value') ?? $entry->prop('placeholder')),
        ])
        ->all();
}

test('a tenants-manage admin can load the tenants table', function () {
    $this->actingAs(globalAdmin())->loadTable(AdminTenantsTable::class)->assertOk();
});

test('a tenants-manage admin can open the tenants list page', function () {
    $this->actingAs(globalAdmin())->get('/admin/tenants')->assertOk();
});

test('the tenants table endpoint is forbidden without tenants-manage', function (?string $roleName) {
    $this->actingAs($roleName === null ? User::factory()->create() : globalAdmin($roleName))
        ->loadDeniedTable(AdminTenantsTable::class)
        ->assertForbidden();
})->with(['non-admin' => null, 'support admin' => GlobalRoleSeeder::SUPPORT]);

test('the tenants list is unreachable without tenants-manage', function (?string $roleName) {
    $this->actingAs($roleName === null ? User::factory()->create() : globalAdmin($roleName))
        ->get('/admin/tenants')
        ->assertForbidden();
})->with(['non-admin' => null, 'support admin' => GlobalRoleSeeder::SUPPORT]);

test('a tenants-manage admin can open a tenant detail page', function () {
    $tenant = User::factory()->create()->currentTenant;
    assumeNotNull($tenant);

    $this->actingAs(globalAdmin())->get("/admin/tenants/{$tenant->slug}")->assertOk();
});

test('a tenant detail page is unreachable without tenants-manage', function (?string $roleName) {
    $tenant = User::factory()->create()->currentTenant;
    assumeNotNull($tenant);

    $this->actingAs($roleName === null ? User::factory()->create() : globalAdmin($roleName))
        ->get("/admin/tenants/{$tenant->slug}")
        ->assertForbidden();
})->with(['non-admin' => null, 'support admin' => GlobalRoleSeeder::SUPPORT]);

test('the detail page lists the owner first and labels a member without roles', function () {
    [$owner, $tenant] = tenantMember(RolePreset::Owner);
    $owner->update(['name' => 'Zoe Owner']);
    addMember($tenant, User::factory()->create(['name' => 'Alice Member']), RolePreset::Member);

    $roleless = User::factory()->create(['name' => 'Bob Roleless']);
    $tenant->memberships()->create(['user_id' => $roleless->id]);

    $response = $this->actingAs(globalAdmin())->get("/admin/tenants/{$tenant->slug}")->assertOk();

    expect(detailRowsOf($response, __('admin.tenants.detail.members.heading')))->toBe([
        ['Zoe Owner', RolePreset::Owner->label()],
        ['Alice Member', RolePreset::Member->label()],
        ['Bob Roleless', __('admin.tenants.detail.no-roles')],
    ]);
});

test('the detail page reports a tenant without an owner or members', function () {
    $tenant = Tenant::factory()->create();

    $response = $this->actingAs(globalAdmin())->get("/admin/tenants/{$tenant->slug}")->assertOk();

    expect(detailRowsOf($response, __('admin.tenants.detail.summary.heading')))
        ->toContain([__('admin.tenants.detail.owner'), __('admin.tenants.detail.no-owner')]);

    $response->assertSee(__('admin.tenants.detail.no-members'), false);
});

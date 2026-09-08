<?php

declare(strict_types=1);

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Admin\Ui\Tables\AdminRealmsTable;
use App\Auth\Models\User;
use App\Realms\Enums\RolePreset;
use App\Realms\Models\Realm;
use Illuminate\Http\Response;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Lattice\Support\Testing\ComponentNode;

use function Tests\Helpers\addMember;
use function Tests\Helpers\globalAdmin;
use function Tests\Helpers\realmMember;

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

test('a realms-manage admin can load the realms table', function () {
    $this->actingAs(globalAdmin())->loadTable(AdminRealmsTable::class)->assertOk();
});

test('a realms-manage admin can open the realms list page', function () {
    $this->actingAs(globalAdmin())->get('/admin/realms')->assertOk();
});

test('the realms table endpoint is forbidden without realms-manage', function (?string $roleName) {
    $this->actingAs($roleName === null ? User::factory()->create() : globalAdmin($roleName))
        ->loadDeniedTable(AdminRealmsTable::class)
        ->assertForbidden();
})->with(['non-admin' => null, 'support admin' => GlobalRoleSeeder::SUPPORT]);

test('the realms list is unreachable without realms-manage', function (?string $roleName) {
    $this->actingAs($roleName === null ? User::factory()->create() : globalAdmin($roleName))
        ->get('/admin/realms')
        ->assertForbidden();
})->with(['non-admin' => null, 'support admin' => GlobalRoleSeeder::SUPPORT]);

test('a realms-manage admin can open a realm detail page', function () {
    $realm = Realm::factory()->create();

    $this->actingAs(globalAdmin())->get("/admin/realms/{$realm->slug}")->assertOk();
});

test('a realm detail page is unreachable without realms-manage', function (?string $roleName) {
    $realm = Realm::factory()->create();

    $this->actingAs($roleName === null ? User::factory()->create() : globalAdmin($roleName))
        ->get("/admin/realms/{$realm->slug}")
        ->assertForbidden();
})->with(['non-admin' => null, 'support admin' => GlobalRoleSeeder::SUPPORT]);

test('the detail page lists the owner first and labels a member without roles', function () {
    [$owner, $realm] = realmMember(RolePreset::Owner);
    $owner->update(['name' => 'Zoe Owner']);
    addMember($realm, User::factory()->for($realm)->create(['name' => 'Alice Member']), RolePreset::Member);

    // A user of the realm holding no role at all.
    User::factory()->for($realm)->create(['name' => 'Bob Roleless']);

    $response = $this->actingAs(globalAdmin())->get("/admin/realms/{$realm->slug}")->assertOk();

    expect(detailRowsOf($response, __('admin.realms.detail.members.heading')))->toBe([
        ['Zoe Owner', RolePreset::Owner->label()],
        ['Alice Member', RolePreset::Member->label()],
        ['Bob Roleless', __('admin.realms.detail.no-roles')],
    ]);
});

test('the detail page reports a realm without an owner or members', function () {
    $realm = Realm::factory()->create();

    $response = $this->actingAs(globalAdmin())->get("/admin/realms/{$realm->slug}")->assertOk();

    expect(detailRowsOf($response, __('admin.realms.detail.summary.heading')))
        ->toContain([__('admin.realms.detail.owner'), __('admin.realms.detail.no-owner')]);

    $response->assertSee(__('admin.realms.detail.no-members'), false);
});

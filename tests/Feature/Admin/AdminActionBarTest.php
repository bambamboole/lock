<?php

declare(strict_types=1);

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Admin\Ui\Tables\AdminClientsTable;
use App\Admin\Ui\Tables\AdminUsersTable;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use Bambamboole\LaravelOidc\Server\Clients\ClientRepository;
use Bambamboole\LaravelOidc\Server\Clients\Models\Client;
use Illuminate\Http\Response;
use Illuminate\Testing\TestResponse;
use Lattice\Support\Testing\ComponentNode;

use function Tests\Helpers\globalAdmin;
use function Tests\Helpers\headerSlot;
use function Tests\Helpers\rowMenus;
use function Tests\Helpers\triggerIds;

/**
 * The two console actions that run without a confirmation dialog: both send a
 * mail the recipient can ignore. Everything else changes or removes state, and
 * with destructive actions styled like every other one the dialog is all that
 * stands in front of them.
 */
const HARMLESS_ACTIONS = [
    'admin.users.resend-verification',
    'admin.users.send-password-reset',
];

beforeEach(function () {
    (new GlobalRoleSeeder)->run();
    $this->realm = Realm::factory()->create(['slug' => 'acme']);
    $this->target = User::factory()->for($this->realm)->create();
    $this->client = $this->realm->runAsCurrent(fn (): Client => app(ClientRepository::class)
        ->createAuthorizationCodeGrantClient('Portal', ['https://rp.test/callback'], true));
    $this->actingAs(globalAdmin());
});

/**
 * A header's own buttons. Actions inside the overflow menu are styled by the
 * menu and carry no emphasis of their own.
 *
 * @param  TestResponse<Response>  $response
 * @return array<int, ComponentNode>
 */
function headerButtons(TestResponse $response): array
{
    $slot = headerSlot($response);

    if (! $slot instanceof ComponentNode) {
        return [];
    }

    $menu = $slot->firstOfType('action.group');
    $inMenu = $menu instanceof ComponentNode ? triggerIds($menu) : [];

    return collect($slot->findAll(fn (ComponentNode $node): bool => in_array($node->type(), ['action', 'button'], true)))
        ->reject(fn (ComponentNode $node): bool => in_array($node->id() ?? $node->key() ?? '', $inMenu, true))
        ->values()
        ->all();
}

test('no action a console header offers renders unstyled', function (string $url) {
    $buttons = headerButtons($this->get($url)->assertOk());
    $unstyled = collect($buttons)
        ->filter(fn (ComponentNode $node): bool => $node->prop('emphasis') === null)
        ->map(fn (ComponentNode $node): mixed => $node->id() ?? $node->key())
        ->all();

    expect($buttons)->not->toBeEmpty()
        ->and($unstyled)->toBeEmpty();
})->with([
    'realms' => '/admin/realms',
    'users' => '/admin/realms/acme/users',
    'clients' => '/admin/realms/acme/clients',
    'roles' => '/admin/realms/acme/roles',
    'user detail' => fn () => "/admin/realms/acme/users/{$this->target->id}",
    'client detail' => fn () => "/admin/realms/acme/clients/{$this->client->id}",
]);

test('every console action that changes something confirms first', function () {
    $surfaces = collect([
        headerSlot($this->get("/admin/realms/acme/users/{$this->target->id}")->assertOk()),
        headerSlot($this->get("/admin/realms/acme/clients/{$this->client->id}")->assertOk()),
        ...rowMenus($this->loadTable(AdminUsersTable::class, context: ['realm' => 'acme'])->assertOk()->json('data')),
        ...rowMenus($this->loadTable(AdminClientsTable::class, context: ['realm' => 'acme'])->assertOk()->json('data')),
    ])->filter(fn (mixed $node): bool => $node instanceof ComponentNode);

    $unconfirmed = $surfaces
        ->flatMap(fn (ComponentNode $node): array => $node->findAll(fn (ComponentNode $item): bool => $item->type() === 'action'))
        ->reject(fn (ComponentNode $node): bool => in_array((string) $node->id(), HARMLESS_ACTIONS, true))
        ->filter(fn (ComponentNode $node): bool => $node->prop('confirmation') === null)
        ->map(fn (ComponentNode $node): string => (string) $node->id())
        ->unique()
        ->values()
        ->all();

    expect($surfaces)->toHaveCount(4)
        ->and($unconfirmed)->toBeEmpty();
});

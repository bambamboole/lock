<?php

declare(strict_types=1);

use App\Admin\Ui\Tables\AdminClientsTable;
use App\Admin\Ui\Tables\AdminUsersTable;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use Lattice\Support\Testing\ComponentNode;

use function Tests\Helpers\globalAdmin;
use function Tests\Helpers\headerSlot;
use function Tests\Helpers\realmClient;
use function Tests\Helpers\rowMenus;

/**
 * Both only send a mail the recipient can ignore; everything else changes state
 * and needs the confirmation dialog in front of it.
 */
const HARMLESS_ACTIONS = [
    'admin.users.resend-verification',
    'admin.users.send-password-reset',
];

beforeEach(function () {
    $this->realm = Realm::factory()->create(['slug' => 'acme']);
    $this->target = User::factory()->for($this->realm)->create();
    $this->client = realmClient($this->realm, 'Portal');
    $this->actingAs(globalAdmin());
});

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

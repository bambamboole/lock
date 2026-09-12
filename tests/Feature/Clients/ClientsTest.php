<?php

declare(strict_types=1);

use App\Auth\Models\User;
use App\Clients\Ui\Actions\DeleteClient;
use App\Clients\Ui\Actions\RestoreClient;
use App\Clients\Ui\Actions\RevokeClient;
use App\Clients\Ui\Actions\RotateClientSecret;
use App\Clients\Ui\Forms\CreateClientForm;
use App\Clients\Ui\Forms\UpdateClientForm;
use App\Clients\Ui\Tables\ClientsTable;
use App\Realms\Models\Realm;
use App\Shared\Api\ManagementRoles;
use App\Shared\Audit\Models\Activity;
use App\Shared\Clients\Enums\ClientAuditEvent;
use Bambamboole\LaravelOidc\Server\Clients\ClientRepository;
use Bambamboole\LaravelOidc\Server\Clients\Enums\TokenEndpointAuthMethod;
use Bambamboole\LaravelOidc\Server\Clients\Models\Client;
use Bambamboole\LaravelOidc\Server\Tokens\Models\AccessToken;
use Illuminate\Support\Facades\Hash;
use Lattice\Support\Testing\ComponentNode;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function Tests\Helpers\globalAdmin;
use function Tests\Helpers\headerTriggers;
use function Tests\Helpers\latticeSchema;
use function Tests\Helpers\realmClient;

beforeEach(function () {
    $this->realm = Realm::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
    $this->other = Realm::factory()->create(['name' => 'Globex', 'slug' => 'globex']);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function clientFormData(array $overrides = []): array
{
    return [
        'name' => 'Partner portal',
        'token_endpoint_auth_method' => TokenEndpointAuthMethod::ClientSecretBasic->value,
        'grant_types' => ['authorization_code', 'refresh_token'],
        'redirect_uris' => "https://portal.test/callback\nhttps://portal.test/alt",
        'post_logout_redirect_uris' => 'https://portal.test/',
        ...$overrides,
    ];
}

/**
 * @return array{0: array<string, mixed>, 1: array<string, string>}
 */
function clientSetting(Client $client, string $field, mixed $value): array
{
    return [[$field => $value], ['realm' => $client->realm_id, 'client' => $client->id, 'field' => $field]];
}

/**
 * @return list<string>
 */
function revealedSecrets(ComponentNode $schema): array
{
    $secrets = [];

    foreach ($schema->findAll(fn (ComponentNode $node): bool => $node->type() === 'code-block' && $node->key() === 'client-secret-value') as $node) {
        $code = $node->prop('code');

        if (is_string($code)) {
            $secrets[] = $code;
        }
    }

    return $secrets;
}

test('a realms-manage admin can open the clients pages and load the table', function () {
    $client = realmClient($this->realm);

    $this->actingAs(globalAdmin());
    $this->get('/admin/realms/acme/clients')->assertOk();
    $this->get('/admin/realms/acme/clients/create')->assertOk();
    $this->get("/admin/realms/acme/clients/{$client->id}")->assertOk();
    $this->loadTable(ClientsTable::class, context: ['realm' => 'acme'])->assertOk();
});

test('the clients console is forbidden without realms-manage', function (Closure $user) {
    $client = realmClient($this->realm);
    $context = ['realm' => 'acme', 'client' => $client->id];

    $this->actingAs($user());
    $this->get('/admin/realms/acme/clients')->assertForbidden();
    $this->get("/admin/realms/acme/clients/{$client->id}")->assertForbidden();
    $this->loadDeniedTable(ClientsTable::class, context: ['realm' => 'acme'])->assertForbidden();
    $this->submitDeniedForm(CreateClientForm::class, clientFormData(), ['realm' => 'acme'])->assertForbidden();
    $this->submitDeniedForm(UpdateClientForm::class, ...clientSetting($client, 'name', 'Renamed'))->assertForbidden();
    $this->callDeniedAction(RotateClientSecret::class, [], $context)->assertForbidden();
    $this->callDeniedAction(RevokeClient::class, [], $context)->assertForbidden();
    $this->callDeniedAction(DeleteClient::class, [], $context)->assertForbidden();

    expect(Client::query()->count())->toBe(1);
})->with([
    'support admin' => fn () => globalAdmin(ManagementRoles::SUPPORT),
    'realm user' => fn () => User::factory()->create(),
]);

test('the clients table lists only the selected realm and searches name and client id', function () {
    $byName = realmClient($this->realm, 'Needle app');
    $byClientId = realmClient($this->realm, 'Other app');
    $byClientId->forceFill(['client_id' => 'needle-client'])->save();
    realmClient($this->realm, 'Unrelated app');
    realmClient($this->other, 'Needle elsewhere');

    $rows = $this->actingAs(globalAdmin())->loadTable(ClientsTable::class, ['q' => 'needle'], ['realm' => 'acme'])->assertOk()->json('data');

    expect(collect(is_array($rows) ? $rows : [])->pluck('id')->all())->toEqualCanonicalizing([$byName->id, $byClientId->id]);
});

test('a client row refuses a field the client does not have', function () {
    $client = realmClient($this->realm);

    $this->actingAs(globalAdmin());

    expect(fn () => $this->submitForm(UpdateClientForm::class, ...clientSetting($client, 'client_id', 'injected')))
        ->toThrow(NotFoundHttpException::class)
        ->and($client->refresh()->client_id)->not->toBe('injected');
});

test('a client detail page opens under its own realm only', function () {
    $client = realmClient($this->realm);

    $this->actingAs(globalAdmin());
    $this->get("/admin/realms/globex/clients/{$client->id}")->assertNotFound();
    $this->submitDeniedForm(UpdateClientForm::class, ['name' => 'Renamed'], ['realm' => 'globex', 'client' => $client->id, 'field' => 'name'])->assertForbidden();
    $this->callDeniedAction(RevokeClient::class, [], ['realm' => 'globex', 'client' => $client->id])->assertForbidden();
    $this->callDeniedAction(DeleteClient::class, [], ['realm' => 'globex', 'client' => $client->id])->assertForbidden();

    expect($client->refresh()->isRevoked())->toBeFalse()
        ->and($client->name)->toBe('Existing app');
});

test('creating a confidential client reveals its secret exactly once', function () {
    $this->realm->writeSettings(['default_scopes' => ['openid', 'profile']]);

    $this->actingAs(globalAdmin())->submitForm(CreateClientForm::class, clientFormData(), ['realm' => 'acme'])->assertRedirect();

    $client = Client::query()->where('name', 'Partner portal')->sole();
    $detailUrl = "/admin/realms/acme/clients/{$client->id}";

    $secrets = revealedSecrets(latticeSchema($this->get($detailUrl)->assertOk()));

    expect($secrets)->toHaveCount(1)
        ->and(Hash::check($secrets[0], (string) $client->getAttributes()['secret']))->toBeTrue()
        ->and(revealedSecrets(latticeSchema($this->get($detailUrl)->assertOk())))->toBeEmpty()
        ->and($client->realm_id)->toBe('acme')
        ->and($client->token_endpoint_auth_method)->toBe(TokenEndpointAuthMethod::ClientSecretBasic)
        ->and($client->grant_types)->toBe(['authorization_code', 'refresh_token'])
        ->and($client->redirect_uris)->toBe(['https://portal.test/callback', 'https://portal.test/alt'])
        ->and($client->post_logout_redirect_uris)->toBe(['https://portal.test/'])
        ->and($client->default_scopes)->toBe(['openid', 'profile']);

    $activity = Activity::forRealm($this->realm)->where('event', ClientAuditEvent::ClientCreated->eventName())->sole();

    expect($activity->subject_id)->toBe($client->id)
        ->and($activity->getProperty('realm'))->toBe('acme')
        ->and($activity->getProperty('client_id'))->toBe($client->client_id);
});

test('a public client is created without a secret', function () {
    $this->actingAs(globalAdmin())->submitForm(CreateClientForm::class, clientFormData([
        'token_endpoint_auth_method' => TokenEndpointAuthMethod::None->value,
        'grant_types' => ['authorization_code'],
    ]), ['realm' => 'acme'])->assertRedirect();

    $client = Client::query()->where('name', 'Partner portal')->sole();

    expect($client->confidential())->toBeFalse()
        ->and(revealedSecrets(latticeSchema($this->get("/admin/realms/acme/clients/{$client->id}")->assertOk())))->toBeEmpty();
});

test('a machine client only needs the client credentials grant', function () {
    $this->actingAs(globalAdmin())->submitForm(CreateClientForm::class, clientFormData([
        'token_endpoint_auth_method' => TokenEndpointAuthMethod::ClientSecretPost->value,
        'grant_types' => ['client_credentials'],
        'redirect_uris' => '',
        'post_logout_redirect_uris' => '',
    ]), ['realm' => 'acme'])->assertRedirect();

    $client = Client::query()->where('name', 'Partner portal')->sole();

    expect($client->grant_types)->toBe(['client_credentials'])
        ->and($client->redirect_uris)->toBeEmpty()
        ->and($client->confidential())->toBeTrue();
});

test('rejects inconsistent client settings on the matching field', function (array $overrides, string $field) {
    $this->actingAs(globalAdmin())->submitForm(CreateClientForm::class, clientFormData($overrides), ['realm' => 'acme'])->assertInvalid([$field]);

    expect(Client::query()->exists())->toBeFalse();
})->with([
    'refresh token without authorization code' => [['grant_types' => ['refresh_token']], 'grant_types'],
    'client credentials on a public client' => [['token_endpoint_auth_method' => 'none', 'grant_types' => ['authorization_code', 'client_credentials']], 'grant_types'],
    'unknown grant type' => [['grant_types' => ['implicit']], 'grant_types'],
    'no redirect uri for authorization code' => [['redirect_uris' => ''], 'redirect_uris'],
    'relative redirect uri' => [['redirect_uris' => '/callback'], 'redirect_uris'],
    'redirect uri with fragment' => [['redirect_uris' => 'https://portal.test/callback#frag'], 'redirect_uris'],
    'invalid post-logout uri' => [['post_logout_redirect_uris' => 'not a uri'], 'post_logout_redirect_uris'],
]);

test('each client row saves on its own and leaves the rest of the client alone', function () {
    $client = realmClient($this->realm);

    $this->actingAs(globalAdmin());
    $this->submitForm(UpdateClientForm::class, ...clientSetting($client, 'name', 'Renamed app'))
        ->assertRedirect("/admin/realms/acme/clients/{$client->id}");
    $this->submitForm(UpdateClientForm::class, ...clientSetting($client, 'grant_types', ['authorization_code']))->assertRedirect();
    $this->submitForm(UpdateClientForm::class, ...clientSetting($client, 'consent_required', false))->assertRedirect();
    $this->submitForm(UpdateClientForm::class, ...clientSetting($client, 'backchannel_logout_uri', 'https://rp.test/backchannel'))->assertRedirect();

    $client->refresh();

    expect($client->name)->toBe('Renamed app')
        ->and($client->grant_types)->toBe(['authorization_code'])
        ->and($client->redirect_uris)->toBe(['https://rp.test/callback'])
        ->and($client->consent_required)->toBeFalse()
        ->and($client->backchannel_logout_uri)->toBe('https://rp.test/backchannel')
        ->and($client->confidential())->toBeTrue();

    $changes = Activity::forRealm($this->realm)->where('event', ClientAuditEvent::ClientUpdated->eventName())->get()
        ->flatMap(fn (Activity $activity): array => (array) $activity->getProperty('changes'));

    expect($changes['name']['new'])->toBe('Renamed app')
        ->and($changes['consent_required']['new'])->toBeFalse();
});

test('a single client row is validated against the stored client', function () {
    $client = realmClient($this->realm);

    $this->actingAs(globalAdmin());
    $this->submitForm(UpdateClientForm::class, ...clientSetting($client, 'grant_types', ['refresh_token']))
        ->assertInvalid(['grant_types' => __('admin.clients.validation.refresh-token-needs-code')]);
    $this->submitForm(UpdateClientForm::class, ...clientSetting($client, 'redirect_uris', ''))
        ->assertInvalid(['redirect_uris' => __('admin.clients.validation.redirect-uri-required')]);
    $this->submitForm(UpdateClientForm::class, ...clientSetting($client, 'grant_types', ['authorization_code', 'client_credentials']))->assertRedirect();
    $this->submitForm(UpdateClientForm::class, ...clientSetting($client, 'token_endpoint_auth_method', 'none'))
        ->assertInvalid(['token_endpoint_auth_method' => __('admin.clients.validation.client-credentials-needs-secret')]);

    expect($client->refresh()->confidential())->toBeTrue()
        ->and($client->redirect_uris)->toBe(['https://rp.test/callback'])
        ->and($client->grant_types)->toBe(['authorization_code', 'client_credentials']);
});

test('turning a public client confidential mints a secret and back again drops it', function () {
    $client = realmClient($this->realm, confidential: false);
    $detailUrl = "/admin/realms/acme/clients/{$client->id}";

    $this->actingAs(globalAdmin());
    $this->submitForm(UpdateClientForm::class, ...clientSetting($client, 'token_endpoint_auth_method', 'client_secret_post'))->assertRedirect();
    $secrets = revealedSecrets(latticeSchema($this->get($detailUrl)->assertOk()));

    expect($secrets)->toHaveCount(1)
        ->and($client->refresh()->confidential())->toBeTrue()
        ->and(Hash::check($secrets[0], (string) $client->getAttributes()['secret']))->toBeTrue();

    $this->submitForm(UpdateClientForm::class, ...clientSetting($client, 'token_endpoint_auth_method', 'none'))->assertRedirect();

    expect($client->refresh()->confidential())->toBeFalse()
        ->and(revealedSecrets(latticeSchema($this->get($detailUrl)->assertOk())))->toBeEmpty();
});

test('rotating the secret replaces it and reveals the new one once', function () {
    $client = realmClient($this->realm);
    $previousHash = (string) $client->getAttributes()['secret'];
    $detailUrl = "/admin/realms/acme/clients/{$client->id}";

    $this->actingAs(globalAdmin())->callAction(RotateClientSecret::class, [], ['realm' => 'acme', 'client' => $client->id])
        ->assertRedirectsToRoute('admin.realms.clients.show', ['realm' => 'acme', 'client' => $client->id]);

    $secrets = revealedSecrets(latticeSchema($this->get($detailUrl)->assertOk()));
    $currentHash = (string) $client->refresh()->getAttributes()['secret'];

    expect($secrets)->toHaveCount(1)
        ->and($currentHash)->not->toBe($previousHash)
        ->and(Hash::check($secrets[0], $currentHash))->toBeTrue()
        ->and(revealedSecrets(latticeSchema($this->get($detailUrl)->assertOk())))->toBeEmpty()
        ->and(Activity::forRealm($this->realm)->where('event', ClientAuditEvent::ClientSecretRotated->eventName())->sole()->subject_id)->toBe($client->id);
});

test('a public client has no secret to rotate', function () {
    $client = realmClient($this->realm, confidential: false);

    $this->actingAs(globalAdmin())->callDeniedAction(RotateClientSecret::class, [], ['realm' => 'acme', 'client' => $client->id])->assertForbidden();
});

test('blocking a client stops it from being resolved as active until it is unblocked', function () {
    $client = realmClient($this->realm);
    $context = ['realm' => 'acme', 'client' => $client->id];
    $active = fn (): ?Client => $this->realm->runAsCurrent(fn () => app(ClientRepository::class)->findActive($client->client_id));

    $this->actingAs(globalAdmin());
    $this->callDeniedAction(RestoreClient::class, [], $context)->assertForbidden();
    $this->callAction(RevokeClient::class, [], $context)->assertReloadsPage();

    expect($client->refresh()->isRevoked())->toBeTrue()
        ->and($active())->toBeNull()
        ->and(Activity::forRealm($this->realm)->where('event', ClientAuditEvent::ClientRevoked->eventName())->sole()->subject_id)->toBe($client->id);

    $this->callDeniedAction(RevokeClient::class, [], $context)->assertForbidden();
    $this->callAction(RestoreClient::class, [], $context)->assertReloadsPage();

    expect($client->refresh()->isRevoked())->toBeFalse()
        ->and($active())->not->toBeNull()
        ->and(Activity::forRealm($this->realm)->where('event', ClientAuditEvent::ClientRestored->eventName())->sole()->subject_id)->toBe($client->id);
});

test('deleting a client removes what was issued to it and records the deletion', function () {
    $client = realmClient($this->realm);
    $survivor = realmClient($this->realm, 'Survivor app');
    $user = User::factory()->for($this->realm)->create();
    AccessToken::factory()->forClient($client)->forUser($user)->create();
    AccessToken::factory()->forClient($survivor)->forUser($user)->create();

    $this->actingAs(globalAdmin())->callAction(DeleteClient::class, [], ['realm' => 'acme', 'client' => $client->id])
        ->assertRedirectsToRoute('admin.realms.clients', ['realm' => 'acme']);

    expect(Client::query()->pluck('id')->all())->toContain($survivor->id)->not->toContain($client->id)
        ->and(AccessToken::query()->pluck('client_id')->all())->toBe([$survivor->id])
        ->and(Activity::forRealm($this->realm)->where('event', ClientAuditEvent::ClientDeleted->eventName())->sole()->getProperty('client_id'))->toBe($client->client_id);
});

test('a client row opens its detail page and carries the same actions as it', function () {
    $client = realmClient($this->realm);

    $this->actingAs(globalAdmin());
    $detail = $this->get("/admin/realms/acme/clients/{$client->id}")->assertOk();
    $row = $this->loadTable(ClientsTable::class, context: ['realm' => 'acme'])->assertOk()->row($client->id);

    expect($row->clickHref())->toBe("/admin/realms/acme/clients/{$client->id}")
        ->and($row->actionIds())->toBe(headerTriggers($detail));
});

test('a client row menu follows the state of its own client', function () {
    $public = realmClient($this->realm, 'Public app', confidential: false);
    $revoked = realmClient($this->realm, 'Revoked app');

    $this->actingAs(globalAdmin());
    $this->callAction(RevokeClient::class, [], ['realm' => 'acme', 'client' => $revoked->id])->assertOk();
    $table = $this->loadTable(ClientsTable::class, context: ['realm' => 'acme'])->assertOk();

    expect($table->row($public->id)->actionIds())->toBe(['admin.clients.revoke', 'admin.clients.delete'])
        ->and($table->row($revoked->id)->actionIds())->toBe(['admin.clients.rotate-secret', 'admin.clients.restore', 'admin.clients.delete']);
});

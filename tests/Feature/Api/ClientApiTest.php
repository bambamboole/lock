<?php

declare(strict_types=1);

use App\Realms\Models\Realm;
use App\Shared\Authorization\Enums\Scope;
use Lock\Server\Clients\Models\Client;
use Lock\Server\Shared\Clients\TokenEndpointAuthMethod;

use function Tests\Helpers\managementApiToken;
use function Tests\Helpers\realmClient;
use function Tests\Helpers\realmUrl;

beforeEach(function () {
    $this->acme = Realm::factory()->create(['name' => 'Acme', 'slug' => 'acme', 'domain' => 'acme.test']);
    $this->client = realmClient($this->acme, 'Partner portal');
});

function clientsUrl(string $realm = 'acme', string $path = ''): string
{
    return realmUrl(Realm::master(), '/api/v1/realms/'.$realm.'/clients'.$path);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function clientPayload(array $overrides = []): array
{
    return [
        'name' => 'Checkout',
        'token_endpoint_auth_method' => TokenEndpointAuthMethod::ClientSecretBasic->value,
        'grant_types' => ['authorization_code', 'refresh_token'],
        'redirect_uris' => ['https://checkout.test/callback'],
        ...$overrides,
    ];
}

it('refuses a request without a token', function () {
    $this->getJson(clientsUrl())->assertUnauthorized();
});

it('refuses a token that only grants the read scope for a write', function () {
    $this->withToken(managementApiToken($this, Scope::ClientsRead))
        ->postJson(clientsUrl(), clientPayload())
        ->assertForbidden();

    expect(Client::query()->where('name', 'Checkout')->exists())->toBeFalse();
});

it('lists only the clients of the addressed realm', function () {
    $globex = Realm::factory()->create(['name' => 'Globex', 'slug' => 'globex', 'domain' => 'globex.test']);
    realmClient($globex, 'Globex app');

    $this->withToken(managementApiToken($this, Scope::ClientsRead))
        ->getJson(clientsUrl())
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Partner portal')
        ->assertJsonPath('data.0.realm', 'acme');
});

it('filters the list by whether a client is blocked', function () {
    realmClient($this->acme, 'Retired app')->forceFill(['revoked_at' => now()])->save();

    $token = managementApiToken($this, Scope::ClientsRead);

    $this->withToken($token)->getJson(clientsUrl().'?filter[revoked]=true')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Retired app');

    auth()->forgetGuards();

    $this->withToken($token)->getJson(clientsUrl().'?filter[revoked]=false')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Partner portal');
});

it('shows a client by its client id, secret included', function () {
    $this->withToken(managementApiToken($this, Scope::ClientsRead))
        ->getJson(clientsUrl('acme', '/'.$this->client->client_id))
        ->assertOk()
        ->assertJsonPath('data.client_id', $this->client->client_id)
        ->assertJsonPath('data.confidential', true)
        ->assertJsonPath('data.secret', $this->client->secret)
        ->assertJsonPath('data.revoked', false);
});

it('does not reach a client through another realm', function () {
    Realm::factory()->create(['name' => 'Globex', 'slug' => 'globex', 'domain' => 'globex.test']);

    $this->withToken(managementApiToken($this, Scope::ClientsRead))
        ->getJson(clientsUrl('globex', '/'.$this->client->client_id))
        ->assertNotFound();
});

it('creates a confidential client and returns its secret', function () {
    $response = $this->withToken(managementApiToken($this, Scope::ClientsWrite))
        ->postJson(clientsUrl(), clientPayload())
        ->assertCreated()
        ->assertJsonPath('data.name', 'Checkout')
        ->assertJsonPath('data.confidential', true)
        ->assertJsonPath('data.redirect_uris', ['https://checkout.test/callback'])
        ->assertJsonPath('data.consent_required', true);

    $client = Client::query()->where('client_id', $response->json('data.client_id'))->sole();

    expect($client->realm)->toBe('acme')
        ->and($client->secret)->toBe($response->json('data.secret'));
});

it('refuses a grant type combination the client cannot serve', function () {
    $this->withToken(managementApiToken($this, Scope::ClientsWrite))
        ->postJson(clientsUrl(), clientPayload(['grant_types' => ['refresh_token']]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('grant_types');
});

it('refuses the authorization code grant without a redirect uri', function () {
    $this->withToken(managementApiToken($this, Scope::ClientsWrite))
        ->postJson(clientsUrl(), clientPayload(['redirect_uris' => []]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('redirect_uris');
});

it('refuses the authorization code grant when the payload leaves redirect uris out entirely', function () {
    $payload = clientPayload();
    unset($payload['redirect_uris']);

    $this->withToken(managementApiToken($this, Scope::ClientsWrite))
        ->postJson(clientsUrl(), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('grant_types');
});

it('refuses a redirect uri that is not absolute', function () {
    $this->withToken(managementApiToken($this, Scope::ClientsWrite))
        ->postJson(clientsUrl(), clientPayload(['redirect_uris' => ['/callback']]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('redirect_uris');
});

it('refuses an unknown grant type instead of dropping it', function () {
    $this->withToken(managementApiToken($this, Scope::ClientsWrite))
        ->postJson(clientsUrl(), clientPayload(['grant_types' => ['password']]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('grant_types');
});

it('refuses a payload key that maps to no property', function () {
    $this->withToken(managementApiToken($this, Scope::ClientsWrite))
        ->postJson(clientsUrl(), clientPayload(['client_id' => 'chosen-by-me']))
        ->assertUnprocessable();
});

it('keeps what an update leaves out', function () {
    $this->withToken(managementApiToken($this, Scope::ClientsWrite))
        ->patchJson(clientsUrl('acme', '/'.$this->client->client_id), ['name' => 'Renamed'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Renamed')
        ->assertJsonPath('data.redirect_uris', ['https://rp.test/callback']);

    expect($this->client->refresh()->grant_types)->toBe(['authorization_code', 'refresh_token']);
});

it('holds an update to the same invariants as a create', function () {
    $this->withToken(managementApiToken($this, Scope::ClientsWrite))
        ->patchJson(clientsUrl('acme', '/'.$this->client->client_id), ['redirect_uris' => []])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('redirect_uris');

    expect($this->client->refresh()->redirect_uris)->toBe(['https://rp.test/callback']);
});

it('drops the secret when a client is turned public', function () {
    $this->withToken(managementApiToken($this, Scope::ClientsWrite))
        ->patchJson(clientsUrl('acme', '/'.$this->client->client_id), [
            'token_endpoint_auth_method' => TokenEndpointAuthMethod::None->value,
        ])
        ->assertOk()
        ->assertJsonPath('data.confidential', false)
        ->assertJsonPath('data.secret', null);
});

it('deletes a client', function () {
    $this->withToken(managementApiToken($this, Scope::ClientsWrite))
        ->deleteJson(clientsUrl('acme', '/'.$this->client->client_id))
        ->assertNoContent();

    expect(Client::query()->where('client_id', $this->client->client_id)->exists())->toBeFalse();
});

it('answers on the master host only', function () {
    $this->withToken(managementApiToken($this, Scope::ClientsRead))
        ->getJson(realmUrl($this->acme, '/api/v1/realms/acme/clients'))
        ->assertNotFound();
});

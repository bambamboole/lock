<?php

declare(strict_types=1);

use App\Realms\Actions\CreateRealm;
use App\Realms\Actions\UpdateRealmConfiguration;
use App\Realms\Models\Realm;
use Bambamboole\LaravelOidc\Server\Clients\ClientRepository;
use Bambamboole\LaravelOidc\Server\Clients\Models\Client;

it('serves only persisted realms and keeps their issuer URLs separate', function () {
    $this->getJson('/realms/partners/.well-known/openid-configuration')->assertNotFound();

    app(CreateRealm::class)->handle('Partners', 'partners');

    $this->getJson('/realms/partners/.well-known/openid-configuration')
        ->assertOk()->assertJsonPath('issuer', config('app.url').'/realms/partners');
    $this->getJson('/realms/admin/.well-known/openid-configuration')
        ->assertOk()->assertJsonPath('issuer', config('app.url').'/realms/admin');
});

it('applies database registration rules only to the configured realm', function () {
    $realm = Realm::factory()->create(['slug' => 'partners']);
    app(UpdateRealmConfiguration::class)->handle($realm, [
        'dynamic_registration' => true,
        'allowed_redirect_domains' => ['partner.test'],
        'default_scopes' => ['openid', 'profile'],
        'optional_scopes' => [],
    ]);

    $this->postJson('/realms/admin/oauth/register', ['redirect_uris' => ['https://partner.test/callback']])->assertNotFound();
    $this->postJson('/realms/partners/oauth/register', ['redirect_uris' => ['https://other.test/callback']])
        ->assertBadRequest()->assertJsonPath('error', 'invalid_redirect_uri');

    $response = $this->postJson('/realms/partners/oauth/register', ['redirect_uris' => ['https://partner.test/callback']])
        ->assertCreated()->assertJsonPath('scope', 'openid profile');

    expect(Client::query()->where('client_id', $response->json('client_id'))->sole()->realm_id)->toBe('partners');

    app(UpdateRealmConfiguration::class)->handle($realm, ['dynamic_registration' => false]);

    $this->postJson('/realms/partners/oauth/register', ['redirect_uris' => ['https://partner.test/callback']])->assertNotFound();
});

it('uses each realms persisted token lifetime and rejects clients from another realm', function () {
    $realm = Realm::factory()->create(['slug' => 'partners']);
    $client = $realm->runAsCurrent(fn () => app(ClientRepository::class)->createClientCredentialsGrantClient('Partner service'));

    $credentials = [
        'grant_type' => 'client_credentials',
        'client_id' => $client->client_id,
        'client_secret' => $client->plainSecret,
        'scope' => 'profile',
    ];

    app(UpdateRealmConfiguration::class)->handle($realm, ['client_credentials_lifetime' => 120]);

    $this->postJson('/realms/partners/oauth/token', $credentials)->assertOk()->assertJsonPath('expires_in', 120);
    $this->postJson('/realms/admin/oauth/token', $credentials)->assertUnauthorized()->assertJsonPath('error', 'invalid_client');

    app(UpdateRealmConfiguration::class)->handle($realm, ['client_credentials_lifetime' => 240]);

    $this->postJson('/realms/partners/oauth/token', $credentials)->assertOk()->assertJsonPath('expires_in', 240);
});

<?php

declare(strict_types=1);

use App\Realms\Models\Realm;
use App\Shared\Api\Enums\ApiScope;
use App\Shared\Api\ManagementApi;
use App\Shared\Realms\Enums\RealmDomainStatus;

use function Tests\Helpers\domainReachesThisInstance;
use function Tests\Helpers\managementApiToken;
use function Tests\Helpers\provisionManagementApi;
use function Tests\Helpers\realmUrl;

beforeEach(function () {
    domainReachesThisInstance();

    $this->acme = Realm::factory()->create(['name' => 'Acme', 'slug' => 'acme', 'domain' => 'acme.test']);
});

function realmsUrl(string $path = ''): string
{
    return realmUrl(Realm::master(), '/api/v1/realms'.$path);
}

it('refuses a request without a token', function () {
    $this->getJson(realmsUrl())->assertUnauthorized();
});

it('refuses a token that only grants the read scope for a write', function () {
    $this->withToken(managementApiToken($this, ApiScope::RealmsRead))
        ->postJson(realmsUrl(), ['name' => 'Globex', 'slug' => 'globex', 'domain' => 'globex.test'])
        ->assertForbidden()
        ->assertJsonPath('error', 'insufficient_scope');

    expect(Realm::query()->where('slug', 'globex')->exists())->toBeFalse();
});

it('refuses a token addressed to the realm itself rather than to this API', function () {
    provisionManagementApi();

    $token = $this->issueClientToken($this->createOidcMachineClient(), [ApiScope::RealmsRead->value]);

    $this->withToken($token)->getJson(realmsUrl())
        ->assertUnauthorized()
        ->assertJsonPath('error', 'invalid_token');
});

it('lists every realm and filters by slug', function () {
    $token = managementApiToken($this, ApiScope::RealmsRead);

    $this->withToken($token)->getJson(realmsUrl())
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Acme');

    auth()->forgetGuards();

    $this->withToken($token)->getJson(realmsUrl().'?filter[slug]=acme')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Acme')
        ->assertJsonPath('data.0.domain', 'acme.test')
        ->assertJsonPath('data.0.master', false);
});

it('shows a realm by slug and reports the master realm as such', function () {
    $this->withToken(managementApiToken($this, ApiScope::RealmsRead))
        ->getJson(realmsUrl('/'.Realm::master()->slug))
        ->assertOk()
        ->assertJsonPath('data.master', true)
        ->assertJsonPath('data.domain', null)
        ->assertJsonPath('data.host', Realm::masterHost());
});

it('creates a realm with its own signing key and a verified domain', function () {
    $this->withToken(managementApiToken($this, ApiScope::RealmsWrite))
        ->postJson(realmsUrl(), ['name' => 'Globex', 'slug' => 'globex', 'domain' => 'globex.test'])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'globex')
        ->assertJsonPath('data.domain_status', RealmDomainStatus::Verified->value);

    $this->getJson(realmUrl(Realm::query()->where('slug', 'globex')->sole(), '/.well-known/jwks.json'))
        ->assertOk()
        ->assertJsonCount(1, 'keys');
});

it('rejects a slug that is already taken', function () {
    $this->withToken(managementApiToken($this, ApiScope::RealmsWrite))
        ->postJson(realmsUrl(), ['name' => 'Other Acme', 'slug' => 'acme', 'domain' => 'other.test'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('slug');
});

it('renames a realm', function () {
    $this->withToken(managementApiToken($this, ApiScope::RealmsWrite))
        ->patchJson(realmsUrl('/acme'), ['name' => 'Acme Inc'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Acme Inc');

    expect($this->acme->refresh()->name)->toBe('Acme Inc');
});

it('re-points a domain and re-checks it', function () {
    $this->acme->forceFill(['domain_status' => RealmDomainStatus::Verified])->save();

    $this->withToken(managementApiToken($this, ApiScope::RealmsWrite))
        ->patchJson(realmsUrl('/acme'), ['domain' => 'acme.example'])
        ->assertOk()
        ->assertJsonPath('data.domain', 'acme.example')
        ->assertJsonPath('data.domain_status', RealmDomainStatus::Verified->value);

    expect($this->acme->refresh()->domain)->toBe('acme.example');
});

it('refuses to give the master realm a domain of its own', function () {
    $this->withToken(managementApiToken($this, ApiScope::RealmsWrite))
        ->patchJson(realmsUrl('/'.Realm::master()->slug), ['domain' => 'takeover.test'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('domain');

    expect(Realm::master()->domain)->toBeNull();
});

it('deletes a realm and frees its slug', function () {
    $this->withToken(managementApiToken($this, ApiScope::RealmsWrite))
        ->deleteJson(realmsUrl('/acme'))
        ->assertNoContent();

    expect(Realm::query()->where('slug', 'acme')->exists())->toBeFalse();
});

it('refuses to delete the master realm', function () {
    $this->withToken(managementApiToken($this, ApiScope::RealmsWrite))
        ->deleteJson(realmsUrl('/'.Realm::master()->slug))
        ->assertStatus(409);

    expect(Realm::query()->where('slug', Realm::master()->slug)->exists())->toBeTrue();
});

it('answers on the master host only', function () {
    $this->withToken(managementApiToken($this, ApiScope::RealmsRead))
        ->getJson(realmUrl($this->acme, '/api/v1/realms'))
        ->assertNotFound();
});

it('declares its scopes as the resource a token is addressed to', function () {
    provisionManagementApi();

    expect(app(ManagementApi::class)->audience())->toBe(Realm::master()->origin().'/api');

    $this->getJson(realmUrl(Realm::master(), '/.well-known/oauth-protected-resource/api'))
        ->assertOk()
        ->assertJsonPath('scopes_supported', ApiScope::values());
});

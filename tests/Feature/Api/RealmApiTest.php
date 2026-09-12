<?php

declare(strict_types=1);

use App\Realms\Models\Realm;
use App\Shared\Api\Enums\ApiScope;
use App\Shared\Api\ManagementApi;
use App\Shared\Audit\Models\Activity;
use App\Shared\Realms\Enums\RealmAuditEvent;
use App\Shared\Realms\Enums\RealmDomainStatus;
use App\Shared\Realms\Support\RealmConfiguration;

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

it('reports the realm configuration with every setting typed', function () {
    $this->acme->writeSettings(['access_token_lifetime' => 900]);
    $this->acme->writeSettings(['login_methods' => ['passkey']]);

    $configuration = $this->withToken(managementApiToken($this, ApiScope::RealmsRead))
        ->getJson(realmsUrl('/acme'))
        ->assertOk()
        ->json('data.settings');

    expect(array_keys($configuration))->toEqualCanonicalizing(array_keys(RealmConfiguration::defaults()))
        ->and($configuration['access_token_lifetime'])->toBe(900)
        ->and($configuration['login_methods'])->toBe(['passkey'])
        ->and($configuration['password_mixed_case'])->toBeBool();
});

it('updates a subset of the configuration and leaves the rest alone', function () {
    $before = $this->acme->configuration();

    $this->withToken(managementApiToken($this, ApiScope::RealmsWrite))
        ->patchJson(realmsUrl('/acme'), ['settings' => [
            'access_token_lifetime' => 900,
            'mfa_requirement' => 'always',
            'login_methods' => ['password', 'passkey'],
        ]])
        ->assertOk()
        ->assertJsonPath('data.settings.access_token_lifetime', 900)
        ->assertJsonPath('data.settings.mfa_requirement', 'always');

    $after = $this->acme->refresh()->configuration();

    expect($after['login_methods'])->toBe(['password', 'passkey'])
        ->and($after['password_min_length'])->toBe($before['password_min_length'])
        ->and($after['refresh_token_lifetime'])->toBe($before['refresh_token_lifetime']);
});

it('stores a quoted number as the integer the setting holds', function () {
    $this->withToken(managementApiToken($this, ApiScope::RealmsWrite))
        ->patchJson(realmsUrl('/acme'), ['settings' => ['totp_window' => '4']])
        ->assertOk()
        ->assertJsonPath('data.settings.totp_window', 4);

    expect($this->acme->refresh()->configuration()['totp_window'])->toBe(4);
});

it('records a configuration change on the realm trail', function () {
    $this->withToken(managementApiToken($this, ApiScope::RealmsWrite))
        ->patchJson(realmsUrl('/acme'), ['settings' => ['recovery_codes' => 12]])
        ->assertOk();

    $activity = Activity::forRealm($this->acme)
        ->where('event', RealmAuditEvent::RealmUpdated->eventName())
        ->sole();

    expect($activity->getProperty('changes'))->toBe(['recovery_codes' => ['old' => 8, 'new' => 12]])
        ->and($activity->causer_id)->toBeNull();
});

it('rejects a setting that contradicts one it was not sent with', function () {
    $this->acme->writeSettings(['session_absolute_lifetime' => 3600]);

    $this->withToken(managementApiToken($this, ApiScope::RealmsWrite))
        ->patchJson(realmsUrl('/acme'), ['settings' => ['session_token_ttl' => 7200]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('settings.session_token_ttl');

    expect($this->acme->refresh()->configuration()['session_token_ttl'])->not->toBe(7200);
});

it('rejects a value outside the setting’s own rules', function () {
    $this->withToken(managementApiToken($this, ApiScope::RealmsWrite))
        ->patchJson(realmsUrl('/acme'), ['settings' => ['mfa_requirement' => 'whenever']])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('settings.mfa_requirement');
});

it('rejects a setting it does not know', function () {
    $this->withToken(managementApiToken($this, ApiScope::RealmsWrite))
        ->patchJson(realmsUrl('/acme'), ['settings' => ['make_me_admin' => true]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('settings.make_me_admin');
});

it('renames and reconfigures a realm in one request', function () {
    $this->withToken(managementApiToken($this, ApiScope::RealmsWrite))
        ->patchJson(realmsUrl('/acme'), [
            'name' => 'Acme Inc',
            'settings' => ['password_min_length' => 16],
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Acme Inc')
        ->assertJsonPath('data.settings.password_min_length', 16);

    expect(Activity::forRealm($this->acme)->where('event', RealmAuditEvent::RealmUpdated->eventName())->count())->toBe(1);
});

it('creates a realm with a configuration and leaves the rest on the defaults', function () {
    $this->withToken(managementApiToken($this, ApiScope::RealmsWrite))
        ->postJson(realmsUrl(), [
            'name' => 'Globex',
            'slug' => 'globex',
            'domain' => 'globex.test',
            'settings' => ['access_token_lifetime' => 900, 'login_methods' => ['passkey']],
        ])
        ->assertCreated()
        ->assertJsonPath('data.settings.access_token_lifetime', 900)
        ->assertJsonPath('data.settings.login_methods', ['passkey']);

    $configuration = Realm::query()->where('slug', 'globex')->sole()->configuration();
    $defaults = RealmConfiguration::defaults();

    expect($configuration['access_token_lifetime'])->toBe(900)
        ->and($configuration['login_methods'])->toBe(['passkey'])
        ->and($configuration['password_min_length'])->toBe($defaults['password_min_length'])
        ->and($configuration['refresh_token_lifetime'])->toBe($defaults['refresh_token_lifetime']);
});

it('keeps no realm behind when the configuration it was created with is rejected', function () {
    $this->withToken(managementApiToken($this, ApiScope::RealmsWrite))
        ->postJson(realmsUrl(), [
            'name' => 'Globex',
            'slug' => 'globex',
            'domain' => 'globex.test',
            'settings' => ['session_token_ttl' => 31536000],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('settings.session_token_ttl');

    expect(Realm::query()->where('slug', 'globex')->exists())->toBeFalse();
});

it('rejects a setting it does not know on create', function () {
    $this->withToken(managementApiToken($this, ApiScope::RealmsWrite))
        ->postJson(realmsUrl(), [
            'name' => 'Globex',
            'slug' => 'globex',
            'domain' => 'globex.test',
            'settings' => ['make_me_admin' => true],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('settings.make_me_admin');
});

it('records the configuration a realm was created with', function () {
    $this->withToken(managementApiToken($this, ApiScope::RealmsWrite))
        ->postJson(realmsUrl(), [
            'name' => 'Globex',
            'slug' => 'globex',
            'domain' => 'globex.test',
            'settings' => ['recovery_codes' => 12],
        ])
        ->assertCreated();

    $activity = Activity::where('event', RealmAuditEvent::RealmCreated->eventName())->sole();

    expect($activity->getProperty('configuration'))->toBe(['recovery_codes' => 12]);
});

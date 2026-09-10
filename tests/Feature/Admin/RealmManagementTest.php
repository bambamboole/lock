<?php

declare(strict_types=1);

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Admin\Ui\Forms\CreateRealmForm;
use App\Auth\Models\User;
use App\Realms\Enums\RealmAuditEvent;
use App\Realms\Models\Realm;
use App\Realms\Ui\Forms\UpdateRealmConfigurationForm;
use App\Shared\Audit\Models\Activity;
use Bambamboole\LaravelOidc\Server\Clients\ClientRepository;

use function Tests\Helpers\globalAdmin;
use function Tests\Helpers\realmFormData;

beforeEach(function () {
    (new GlobalRoleSeeder)->run();
});

it('creates an independent realm with default configuration through the admin form', function () {
    Realm::master()->settings->set('realm.access_token_lifetime', 120);

    $this->actingAs(globalAdmin())
        ->submitForm(CreateRealmForm::class, ['name' => 'Partners', 'slug' => 'partners'])
        ->assertRedirect(route('realm.settings.edit', ['current_realm' => 'partners', 'tabs' => 'configuration']));

    $realm = Realm::query()->where('slug', 'partners')->sole();

    expect($realm->name)->toBe('Partners')
        ->and($realm->tokens()->accessTokenLifetime)->toBe(900)
        ->and($realm->clients()->dynamicRegistration)->toBeFalse();

    $this->getJson('/realms/partners/.well-known/openid-configuration')->assertOk();
});

it('saves configuration for the signed realm while preserving its identity', function (string $slug) {
    $partner = Realm::factory()->create(['name' => 'Partners', 'slug' => 'partners']);
    $realm = $slug === 'admin' ? Realm::master() : $partner;
    $other = $slug === 'admin' ? $partner : Realm::master();

    $this->actingAs(globalAdmin())->submitForm(UpdateRealmConfigurationForm::class, realmFormData($realm, [
        'name' => 'Updated realm',
        'slug' => 'renamed',
        'access_token_lifetime' => 300,
        'session_cookie_name' => 'custom-identity',
        'session_absolute_lifetime' => 86400,
        'session_token_ttl' => 600,
        'session_token_refresh_skew' => 30,
        'challenge_providers' => ['totp'],
        'recovery_codes' => 10,
        'dynamic_registration' => true,
        'allowed_redirect_domains' => "partner.test\n localhost \n",
        'default_scopes' => "openid\nmember:view",
        'first_party_client_id' => 'injected',
    ]), ['realm' => $realm->slug])->assertRedirect();

    $realm->refresh();

    expect($realm->name)->toBe($slug === 'admin' ? 'Admin' : 'Partners')
        ->and($realm->slug)->toBe($slug)
        ->and($realm->tokens()->accessTokenLifetime)->toBe(300)
        ->and($realm->sessions()->cookieName)->toBe('custom-identity')
        ->and($other->sessions()->cookieName)->toBeNull()
        ->and($realm->sessions()->absoluteLifetime)->toBe(86400)
        ->and($realm->sessions()->tokenTtl)->toBe(600)
        ->and($realm->sessions()->tokenRefreshSkew)->toBe(30)
        ->and($realm->credentials()->challengeProviders)->toBe(['totp'])
        ->and($realm->credentials()->recoveryCodes)->toBe(10)
        ->and($realm->clients()->dynamicRegistration)->toBeTrue()
        ->and($realm->clients()->allowedRedirectDomains)->toBe(['partner.test', 'localhost'])
        ->and($realm->clients()->defaultScopes)->toBe(['openid', 'member:view'])
        ->and($realm->clients()->firstPartyClientId)->toBeNull()
        ->and($other->refresh()->tokens()->accessTokenLifetime)->toBe(900);

    $activity = Activity::query()->where('event', RealmAuditEvent::RealmUpdated->eventName())->sole();

    expect($activity->subject_id)->toBe($realm->id)
        ->and($activity->realm_id)->toBe($realm->id)
        ->and($activity->getProperty('changes.access_token_lifetime.new'))->toBe(300);
})->with(['admin', 'partners']);

it('rejects a realm configuration atomically when session timings conflict', function () {
    $realm = Realm::master();

    $this->actingAs(globalAdmin())->submitForm(UpdateRealmConfigurationForm::class, realmFormData($realm, [
        'access_token_lifetime' => 120,
        'session_token_ttl' => 60,
        'session_token_refresh_skew' => 60,
    ]), ['realm' => $realm->slug])->assertInvalid(['session_token_refresh_skew']);

    expect($realm->refresh()->tokens()->accessTokenLifetime)->toBe(900)
        ->and(Activity::query()->where('event', RealmAuditEvent::RealmUpdated->eventName())->exists())->toBeFalse();
});

it('reports invalid list entries on the matching configuration field', function (string $field, string $value) {
    $realm = Realm::master();
    $before = $realm->configuration();

    $this->actingAs(globalAdmin())
        ->submitForm(UpdateRealmConfigurationForm::class, realmFormData($realm, [$field => $value]), ['realm' => $realm->slug])
        ->assertInvalid([$field]);

    expect($realm->refresh()->configuration())->toBe($before);
})->with([
    'invalid cookie name' => ['session_cookie_name', 'invalid cookie'],
    'unknown scope' => ['default_scopes', 'admin:realms:manage'],
    'unsafe scheme' => ['allowed_redirect_schemes', 'javascript'],
    'URL instead of host' => ['allowed_redirect_domains', 'https://partner.test/path'],
    'unknown trusted client' => ['trusted_clients', 'missing-client'],
]);

it('only trusts active clients belonging to the configured realm', function () {
    $realm = Realm::factory()->create(['slug' => 'partners']);
    $master = Realm::master();
    $client = $master->runAsCurrent(fn () => app(ClientRepository::class)
        ->createAuthorizationCodeGrantClient('Master client', ['https://rp.test/callback']));

    $this->actingAs(globalAdmin())
        ->submitForm(UpdateRealmConfigurationForm::class, realmFormData($realm, ['trusted_clients' => $client->client_id]), ['realm' => $realm->slug])
        ->assertInvalid(['trusted_clients']);

    $this->submitForm(UpdateRealmConfigurationForm::class, realmFormData($master, ['trusted_clients' => $client->client_id]), ['realm' => $master->slug])
        ->assertRedirect()->assertValid();

    expect($master->refresh()->clients()->trustedClients)->toBe([$client->client_id]);
});

it('rejects duplicate and unsafe realm identifiers', function (string $slug) {
    $before = Realm::query()->count();

    $this->actingAs(globalAdmin())
        ->submitForm(CreateRealmForm::class, ['name' => 'Invalid realm', 'slug' => $slug])
        ->assertInvalid(['slug']);

    expect(Realm::query()->count())->toBe($before);
})->with(['admin', '../admin', 'Uppercase', 'space name', 'trailing-']);

it('denies realm creation without the dedicated global permission', function (?string $role) {
    $this->actingAs($role === null ? User::factory()->create() : globalAdmin($role));

    $this->get('/admin/realms/create')->assertForbidden();
    $this->submitDeniedForm(CreateRealmForm::class, ['name' => 'Forbidden', 'slug' => 'forbidden'])->assertForbidden();
})->with(['member' => null, 'support' => GlobalRoleSeeder::SUPPORT]);

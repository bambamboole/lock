<?php

declare(strict_types=1);

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Admin\Ui\Forms\CreateRealmForm;
use App\Admin\Ui\Forms\DeleteRealmForm;
use App\Admin\Ui\Forms\RealmSettingForm;
use App\Admin\Ui\Forms\UpdateRealmForm;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Shared\Audit\Models\Activity;
use App\Shared\Realms\Enums\RealmAuditEvent;
use App\Shared\Realms\Support\RealmConfiguration;
use Bambamboole\LaravelOidc\Server\Clients\ClientRepository;
use Bambamboole\LaravelOidc\Server\Clients\Models\Client;
use Bambamboole\LaravelOidc\Server\Sessions\Models\OidcSession;
use Bambamboole\LaravelOidc\Server\Shared\Realms\Settings\LoginMethod;
use Bambamboole\LaravelOidc\Server\Shared\Realms\Settings\MfaRequirement;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function Tests\Helpers\domainReachesThisInstance;
use function Tests\Helpers\globalAdmin;
use function Tests\Helpers\realmSetting;

beforeEach(function () {
    (new GlobalRoleSeeder)->run();
    $this->actingAs(globalAdmin());
});

it('creates an independent realm with default configuration through the admin form', function () {
    Realm::master()->settings->set('realm.access_token_lifetime', 120);

    domainReachesThisInstance();

    $this->submitForm(CreateRealmForm::class, ['name' => 'Partners', 'slug' => 'partners', 'domain' => 'partners.example.com'])
        ->assertRedirect(route('admin.realms.settings', ['realm' => 'partners', 'tabs' => 'tokens']));

    $realm = Realm::query()->where('slug', 'partners')->sole();

    expect($realm->name)->toBe('Partners')
        ->and($realm->tokens()->accessTokenLifetime)->toBe(900)
        ->and($realm->clients()->dynamicRegistration)->toBeFalse()
        ->and(Activity::global()->where('event', RealmAuditEvent::RealmCreated->eventName())->sole()->subject_id)->toBe($realm->id);

    $this->getJson('http://partners.example.com/.well-known/openid-configuration')->assertOk();
});

it('rejects duplicate and unsafe realm identifiers', function (string $slug) {
    $before = Realm::query()->count();

    $this->submitForm(CreateRealmForm::class, ['name' => 'Invalid realm', 'slug' => $slug, 'domain' => 'invalid.example.com'])->assertInvalid(['slug']);

    expect(Realm::query()->count())->toBe($before);
})->with(['admin', '../admin', 'Uppercase', 'space name', 'trailing-']);

it('renames a realm without touching its identifier or configuration', function () {
    $realm = Realm::factory()->create(['name' => 'Partners', 'slug' => 'partners']);
    $realm->settings->set('realm.access_token_lifetime', 300);

    $this->submitForm(UpdateRealmForm::class, ['name' => 'External partners'], ['realm' => $realm->slug])
        ->assertRedirect(route('admin.realms.settings', ['realm' => 'partners']));

    expect($realm->refresh()->name)->toBe('External partners')
        ->and($realm->slug)->toBe('partners')
        ->and($realm->tokens()->accessTokenLifetime)->toBe(300)
        ->and(Activity::forRealm($realm)->where('event', RealmAuditEvent::RealmUpdated->eventName())->sole()->getProperty('changes.name.new'))->toBe('External partners');
});

it('saves one setting at a time for the signed realm and leaves every other realm alone', function (string $slug) {
    $partner = Realm::factory()->create(['name' => 'Partners', 'slug' => 'partners']);
    $realm = $slug === 'admin' ? Realm::master() : $partner;
    $other = $slug === 'admin' ? $partner : Realm::master();

    $this->submitForm(RealmSettingForm::class, ...realmSetting($realm, 'access_token_lifetime', 300))
        ->assertRedirect(route('admin.realms.settings', ['realm' => $slug, 'tabs' => 'tokens']));
    $this->submitForm(RealmSettingForm::class, ...realmSetting($realm, 'login_methods', ['password', 'passkey']))
        ->assertRedirect(route('admin.realms.settings', ['realm' => $slug, 'tabs' => 'login']));
    $this->submitForm(RealmSettingForm::class, ...realmSetting($realm, 'mfa_requirement', 'always'))
        ->assertRedirect(route('admin.realms.settings', ['realm' => $slug, 'tabs' => 'mfa']));
    $this->submitForm(RealmSettingForm::class, ...realmSetting($realm, 'challenge_providers', ['totp']))
        ->assertRedirect(route('admin.realms.settings', ['realm' => $slug, 'tabs' => 'mfa']));
    $this->submitForm(RealmSettingForm::class, ...realmSetting($realm, 'dynamic_registration', true))
        ->assertRedirect(route('admin.realms.settings', ['realm' => $slug, 'tabs' => 'clients']));
    $this->submitForm(RealmSettingForm::class, ...realmSetting($realm, 'allowed_redirect_domains', "partner.test\n localhost \n"))->assertRedirect();
    $this->submitForm(RealmSettingForm::class, ...realmSetting($realm, 'default_scopes', "openid\nprofile"))->assertRedirect();

    $realm->refresh();

    expect($realm->name)->toBe($slug === 'admin' ? 'Admin' : 'Partners')
        ->and($realm->slug)->toBe($slug)
        ->and($realm->tokens()->accessTokenLifetime)->toBe(300)
        ->and($realm->authentication()->methods)->toBe([LoginMethod::Password, LoginMethod::Passkey])
        ->and($realm->authentication()->mfa)->toBe(MfaRequirement::Always)
        ->and($other->authentication()->mfa)->toBe(MfaRequirement::IfEnrolled)
        ->and($realm->credentials()->challengeProviders)->toBe(['totp'])
        ->and($realm->clients()->dynamicRegistration)->toBeTrue()
        ->and($realm->clients()->allowedRedirectDomains)->toBe(['partner.test', 'localhost'])
        ->and($realm->clients()->defaultScopes)->toBe(['openid', 'profile'])
        ->and($other->refresh()->tokens()->accessTokenLifetime)->toBe(900);

    $activity = Activity::forRealm($realm)->where('event', RealmAuditEvent::RealmUpdated->eventName())->orderBy('id')->first();

    expect($activity?->subject_id)->toBe($realm->id)
        ->and($activity?->getProperty('changes.access_token_lifetime'))->toBe(['old' => 900, 'new' => 300]);
})->with(['admin', 'partners']);

it('refuses a field the realm configuration does not define', function (string $field) {
    $realm = Realm::master();
    $before = $realm->configuration();

    expect(fn () => $this->submitForm(RealmSettingForm::class, ...realmSetting($realm, $field, 'injected')))
        ->toThrow(NotFoundHttpException::class)
        ->and($realm->refresh()->configuration())->toBe($before)
        ->and($realm->name)->toBe('Admin')
        ->and($realm->slug)->toBe('admin');
})->with(['first_party_client_id', 'slug', 'name']);

it('saves the password policy of the signed realm and audits the change', function () {
    $realm = Realm::factory()->create(['slug' => 'partners']);
    $other = Realm::master();

    $this->submitForm(RealmSettingForm::class, ...realmSetting($realm, 'password_min_length', 12))->assertRedirect();
    $this->submitForm(RealmSettingForm::class, ...realmSetting($realm, 'password_mixed_case', true))->assertRedirect();
    $this->submitForm(RealmSettingForm::class, ...realmSetting($realm, 'password_numbers', true))->assertRedirect();
    $this->submitForm(RealmSettingForm::class, ...realmSetting($realm, 'password_history', 5))->assertRedirect();
    $this->submitForm(RealmSettingForm::class, ...realmSetting($realm, 'password_max_age_days', 90))->assertRedirect();

    $policy = $realm->refresh()->credentials()->password;

    expect($policy->minLength)->toBe(12)
        ->and($policy->mixedCase)->toBeTrue()
        ->and($policy->numbers)->toBeTrue()
        ->and($policy->symbols)->toBeFalse()
        ->and($policy->history)->toBe(5)
        ->and($policy->maxAgeDays)->toBe(90)
        ->and($other->credentials()->password->minLength)->toBe(8)
        ->and($other->credentials()->password->maxAgeDays)->toBeNull();

    $changes = Activity::forRealm($realm)->where('event', RealmAuditEvent::RealmUpdated->eventName())->get()
        ->flatMap(fn (Activity $activity): array => (array) $activity->getProperty('changes'));

    expect($changes['password_min_length'])->toBe(['old' => 8, 'new' => 12])
        ->and($changes['password_max_age_days'])->toBe(['old' => 0, 'new' => 90]);
});

it('rejects an out-of-range password policy without saving it', function (string $field, int $value) {
    $realm = Realm::master();
    $before = $realm->configuration();

    $this->submitForm(RealmSettingForm::class, ...realmSetting($realm, $field, $value))->assertInvalid([$field]);

    expect($realm->refresh()->configuration())->toBe($before);
})->with([
    'too short a minimum' => ['password_min_length', 5],
    'negative history' => ['password_history', -1],
    'negative age' => ['password_max_age_days', -1],
]);

it('reports a conflict with a setting the form does not show on the field being edited', function () {
    $realm = Realm::master();

    $this->submitForm(RealmSettingForm::class, ...realmSetting($realm, 'session_token_ttl', 60))
        ->assertInvalid(['session_token_ttl' => __('admin.realms.fields.session-token-refresh-skew.label')]);

    expect($realm->refresh()->sessions()->tokenTtl)->toBe(3600)
        ->and(Activity::query()->where('event', RealmAuditEvent::RealmUpdated->eventName())->exists())->toBeFalse();
});

it('reports invalid list entries on the matching configuration field', function (string $field, string $value) {
    $realm = Realm::master();
    $before = $realm->configuration();

    $this->submitForm(RealmSettingForm::class, ...realmSetting($realm, $field, $value))->assertInvalid([$field]);

    expect($realm->refresh()->configuration())->toBe($before);
})->with([
    'unknown scope' => ['default_scopes', 'admin:realms:manage'],
    'wildcard is not a default scope' => ['default_scopes', '*'],
    'unsafe scheme' => ['allowed_redirect_schemes', 'javascript'],
    'URL instead of host' => ['allowed_redirect_domains', 'https://partner.test/path'],
    'unknown trusted client' => ['trusted_clients', 'missing-client'],
    'unknown mfa requirement' => ['mfa_requirement', 'sometimes'],
]);

it('only trusts active clients belonging to the configured realm', function () {
    $realm = Realm::factory()->create(['slug' => 'partners']);
    $master = Realm::master();
    $client = $master->runAsCurrent(fn () => app(ClientRepository::class)
        ->createAuthorizationCodeGrantClient('Master client', ['https://rp.test/callback']));

    $this->submitForm(RealmSettingForm::class, ...realmSetting($realm, 'trusted_clients', $client->client_id))->assertInvalid(['trusted_clients']);
    $this->submitForm(RealmSettingForm::class, ...realmSetting($master, 'trusted_clients', $client->client_id))->assertRedirect()->assertValid();

    expect($master->refresh()->clients()->trustedClients)->toBe([$client->client_id]);
});

it('deletes a realm with its users, clients and sessions and frees the identifier', function () {
    $realm = Realm::factory()->create(['name' => 'Partners', 'slug' => 'partners']);
    $user = User::factory()->for($realm)->create();
    $client = $realm->runAsCurrent(fn () => app(ClientRepository::class)->createAuthorizationCodeGrantClient('Partner app', ['https://rp.test/callback']));
    OidcSession::query()->forceCreate(['sid' => fake()->uuid(), 'realm_id' => 'partners', 'user_id' => $user->id, 'expires_at' => now()->addHour()]);
    $masterClient = Realm::master()->runAsCurrent(fn () => app(ClientRepository::class)->createAuthorizationCodeGrantClient('Admin app', ['https://rp.test/callback']));

    $this->submitForm(DeleteRealmForm::class, ['name' => 'Partners'], ['realm' => $realm->slug])
        ->assertRedirect(route('admin.realms'));

    expect(Realm::query()->where('slug', 'partners')->exists())->toBeFalse()
        ->and(User::query()->whereKey($user->id)->exists())->toBeFalse()
        ->and(Client::query()->whereKey($client->id)->exists())->toBeFalse()
        ->and(Client::query()->whereKey($masterClient->id)->exists())->toBeTrue()
        ->and(OidcSession::query()->where('realm_id', 'partners')->exists())->toBeFalse()
        ->and(Activity::global()->where('event', RealmAuditEvent::RealmDeleted->eventName())->sole()->getProperty('slug'))->toBe('partners');

    domainReachesThisInstance();

    $this->submitForm(CreateRealmForm::class, ['name' => 'Partners again', 'slug' => 'partners', 'domain' => 'partners.example.com'])->assertRedirect();
    $this->getJson('http://partners.example.com/.well-known/openid-configuration')->assertOk();
});

it('requires the realm name to confirm a deletion', function () {
    $realm = Realm::factory()->create(['name' => 'Partners']);

    $this->submitForm(DeleteRealmForm::class, ['name' => 'Wrong name'], ['realm' => $realm->slug])->assertInvalid(['name']);

    expect(Realm::query()->whereKey($realm->id)->exists())->toBeTrue();
});

it('refuses to delete the admin realm', function () {
    $master = Realm::master();

    $this->submitDeniedForm(DeleteRealmForm::class, ['name' => $master->name], ['realm' => $master->slug])->assertForbidden();

    expect(Realm::query()->whereKey($master->id)->exists())->toBeTrue();
});

it('exposes every stored realm setting through a section of the console', function () {
    expect(array_keys(RealmConfiguration::settings()))
        ->toEqualCanonicalizing(array_keys(RealmConfiguration::defaults()));
});

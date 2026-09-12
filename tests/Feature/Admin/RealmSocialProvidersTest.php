<?php

declare(strict_types=1);

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Actions\CreateSocialProviderAction;
use App\Admin\Ui\Actions\DeleteSocialProviderAction;
use App\Admin\Ui\Forms\UpdateSocialProviderForm;
use App\Admin\Ui\Tables\AdminSocialProvidersTable;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Realms\Models\RealmSocialProvider;
use App\Shared\Audit\Models\Activity;
use App\Shared\Realms\Enums\RealmAuditEvent;
use App\Shared\Realms\Enums\SocialProviderDriver;
use Bambamboole\LaravelOidc\Server\Brokering\Models\SocialAccount;

use function Tests\Helpers\globalAdmin;
use function Tests\Helpers\globalAdminWith;

beforeEach(function () {
    $this->realm = Realm::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
    $this->other = Realm::factory()->create(['name' => 'Globex', 'slug' => 'globex']);
});

test('an admin adds a provider and only the credentials of its driver are stored', function () {
    $this->actingAs(globalAdmin())->callAction(CreateSocialProviderAction::class, [
        'key' => '  google  ',
        'driver' => 'google',
        'client_id' => 'client-id',
        'client_secret' => 'client-secret',
        'issuer' => 'https://ignored.test',
        'enabled' => true,
    ], ['realm' => 'acme'])->assertOk();

    $provider = RealmSocialProvider::query()->sole();

    expect($provider->key)->toBe('google')
        ->and($provider->driver)->toBe(SocialProviderDriver::Google)
        ->and($provider->realm_id)->toBe($this->realm->id)
        ->and($provider->config)->toBe(['client_id' => 'client-id', 'client_secret' => 'client-secret']);

    $activity = Activity::forRealm($this->realm)->where('event', RealmAuditEvent::SocialProviderCreated->eventName())->sole();

    expect($activity->subject_id)->toBe($provider->id)
        ->and($activity->getProperty('key'))->toBe('google')
        ->and($activity->getProperty('driver'))->toBe('google');
});

test('credentials are stored encrypted', function () {
    RealmSocialProvider::factory()->for($this->realm)->create([
        'config' => ['client_id' => 'client-id', 'client_secret' => 'top-secret'],
    ]);

    $stored = (string) DB::table('realm_social_providers')->value('config');

    expect($stored)->not->toContain('top-secret');
});

test('the credentials a driver does not use are not required', function () {
    $this->actingAs(globalAdmin())->callAction(CreateSocialProviderAction::class, [
        'key' => 'apple',
        'driver' => 'apple',
        'client_id' => 'com.acme.login',
        'team_id' => 'TEAM123',
        'key_id' => 'KEY123',
        'private_key' => '-----BEGIN PRIVATE KEY-----',
        'enabled' => true,
    ], ['realm' => 'acme'])->assertOk();

    expect(RealmSocialProvider::query()->sole()->config)->toBe([
        'client_id' => 'com.acme.login',
        'team_id' => 'TEAM123',
        'key_id' => 'KEY123',
        'private_key' => '-----BEGIN PRIVATE KEY-----',
    ]);
});

test('a credential the chosen driver needs is required', function () {
    $this->actingAs(globalAdmin())->callAction(CreateSocialProviderAction::class, [
        'key' => 'acme-sso',
        'driver' => 'oidc',
        'client_id' => 'client-id',
        'client_secret' => 'client-secret',
        'enabled' => true,
    ], ['realm' => 'acme'])->assertInvalid(['issuer']);

    expect(RealmSocialProvider::query()->exists())->toBeFalse();
});

test('a provider key is unique per realm but free in another', function () {
    RealmSocialProvider::factory()->for($this->realm)->create(['key' => 'google']);

    $payload = ['key' => 'google', 'driver' => 'google', 'client_id' => 'id', 'client_secret' => 'secret', 'enabled' => true];

    $this->actingAs(globalAdmin());
    $this->callAction(CreateSocialProviderAction::class, $payload, ['realm' => 'acme'])->assertInvalid(['key']);
    $this->callAction(CreateSocialProviderAction::class, $payload, ['realm' => 'globex'])->assertOk();

    expect(RealmSocialProvider::query()->where('key', 'google')->count())->toBe(2);
});

test('a provider key is rejected when it would not be url safe', function (string $key) {
    $this->actingAs(globalAdmin())->callAction(CreateSocialProviderAction::class, [
        'key' => $key,
        'driver' => 'google',
        'client_id' => 'id',
        'client_secret' => 'secret',
        'enabled' => true,
    ], ['realm' => 'acme'])->assertInvalid(['key']);

    expect(RealmSocialProvider::query()->exists())->toBeFalse();
})->with([
    'whitespace' => 'acme sso',
    'uppercase' => 'Google',
    'leading digit' => '1password',
    'slash' => 'acme/sso',
]);

test('an empty secret keeps the stored one and the trail records only that it changed', function () {
    $provider = RealmSocialProvider::factory()->for($this->realm)->create([
        'key' => 'google',
        'config' => ['client_id' => 'old-id', 'client_secret' => 'keep-me'],
    ]);

    $this->actingAs(globalAdmin());
    $this->submitForm(UpdateSocialProviderForm::class, ['client_id' => 'new-id'], ['realm' => 'acme', 'socialProvider' => $provider->id, 'field' => 'client_id'])->assertRedirect();
    $this->submitForm(UpdateSocialProviderForm::class, ['enabled' => false], ['realm' => 'acme', 'socialProvider' => $provider->id, 'field' => 'enabled'])->assertRedirect();

    expect($provider->refresh()->config)->toBe(['client_id' => 'new-id', 'client_secret' => 'keep-me'])
        ->and($provider->enabled)->toBeFalse();

    $changes = Activity::forRealm($this->realm)
        ->where('event', RealmAuditEvent::SocialProviderUpdated->eventName())
        ->get()
        ->flatMap(fn (Activity $activity): array => (array) $activity->getProperty('changes'))
        ->all();

    expect($changes)->toBe([
        'client_id' => ['old' => 'old-id', 'new' => 'new-id'],
        'enabled' => ['old' => true, 'new' => false],
    ]);
});

test('a submitted secret replaces the stored one without reaching the trail', function () {
    $provider = RealmSocialProvider::factory()->for($this->realm)->create([
        'key' => 'google',
        'config' => ['client_id' => 'id', 'client_secret' => 'old-secret'],
    ]);

    $this->actingAs(globalAdmin())->submitForm(UpdateSocialProviderForm::class, [
        'client_secret' => 'new-secret',
    ], ['realm' => 'acme', 'socialProvider' => $provider->id, 'field' => 'client_secret'])->assertRedirect();

    expect($provider->refresh()->config['client_secret'])->toBe('new-secret');

    $activity = Activity::forRealm($this->realm)->where('event', RealmAuditEvent::SocialProviderUpdated->eventName())->sole();

    expect($activity->getProperty('changes'))->toBe(['client_secret' => ['replaced' => true]]);
});

test('a credential the provider needs cannot be cleared on an edit', function () {
    $provider = RealmSocialProvider::factory()->for($this->realm)->create([
        'key' => 'google',
        'config' => ['client_id' => 'id', 'client_secret' => 'secret'],
    ]);

    $this->actingAs(globalAdmin())->submitForm(UpdateSocialProviderForm::class, [
        'client_id' => '',
    ], ['realm' => 'acme', 'socialProvider' => $provider->id, 'field' => 'client_id'])->assertInvalid(['client_id']);

    expect($provider->refresh()->config['client_id'])->toBe('id');
});

test('deleting a provider unlinks the accounts brokered through it', function () {
    $provider = RealmSocialProvider::factory()->for($this->realm)->create(['key' => 'google']);
    $user = User::factory()->for($this->realm)->create();

    $account = new SocialAccount;
    $account->forceFill(['realm_id' => 'acme', 'provider' => 'google', 'provider_user_id' => 'upstream-1']);
    $account->authenticatable()->associate($user);
    $account->save();

    $this->actingAs(globalAdmin())
        ->callAction(DeleteSocialProviderAction::class, [], ['realm' => 'acme', 'socialProvider' => $provider->id])
        ->assertOk();

    expect(RealmSocialProvider::query()->exists())->toBeFalse()
        ->and(SocialAccount::query()->exists())->toBeFalse();

    $activity = Activity::forRealm($this->realm)->where('event', RealmAuditEvent::SocialProviderDeleted->eventName())->sole();

    expect($activity->getProperty('accounts-unlinked'))->toBe(1);
});

test('the providers table lists only the providers of the selected realm', function () {
    RealmSocialProvider::factory()->for($this->realm)->create(['key' => 'google']);
    RealmSocialProvider::factory()->for($this->realm)->oidc('acme-sso')->create();
    RealmSocialProvider::factory()->for($this->other)->create(['key' => 'github']);

    $rows = $this->actingAs(globalAdmin())
        ->loadTable(AdminSocialProvidersTable::class, context: ['realm' => 'acme'])
        ->assertOk()
        ->json('data');

    $rows = collect(is_array($rows) ? $rows : []);

    expect($rows->pluck('key')->all())->toBe(['acme-sso', 'google'])
        ->and($rows->flatMap(array_keys(...))->all())->not->toContain('config');
});

test('a provider is only reachable through its own realm', function () {
    $provider = RealmSocialProvider::factory()->for($this->realm)->create(['key' => 'google']);
    $foreign = ['realm' => 'globex', 'socialProvider' => $provider->id];

    $this->actingAs(globalAdmin());
    $this->get("/admin/realms/globex/social-providers/{$provider->id}")->assertNotFound();
    $this->submitDeniedForm(UpdateSocialProviderForm::class, ['client_id' => 'taken-over'], [...$foreign, 'field' => 'client_id'])->assertForbidden();
    $this->callDeniedAction(DeleteSocialProviderAction::class, [], $foreign)->assertForbidden();

    expect($provider->refresh()->config['client_id'])->not->toBe('taken-over');
});

test('the providers console is forbidden without realms-manage', function () {
    $provider = RealmSocialProvider::factory()->for($this->realm)->create(['key' => 'google']);
    $context = ['realm' => 'acme', 'socialProvider' => $provider->id];

    $this->actingAs(globalAdminWith(AdminPermission::Access, AdminPermission::ViewRealms));
    $this->get("/admin/realms/acme/social-providers/{$provider->id}")->assertForbidden();
    $this->loadDeniedTable(AdminSocialProvidersTable::class, context: ['realm' => 'acme'])->assertForbidden();
    $this->callDeniedAction(CreateSocialProviderAction::class, ['key' => 'github', 'driver' => 'github'], ['realm' => 'acme'])->assertForbidden();
    $this->submitDeniedForm(UpdateSocialProviderForm::class, ['client_id' => 'other'], [...$context, 'field' => 'client_id'])->assertForbidden();
    $this->callDeniedAction(DeleteSocialProviderAction::class, [], $context)->assertForbidden();

    expect(RealmSocialProvider::query()->count())->toBe(1);
});

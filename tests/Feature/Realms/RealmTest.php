<?php
declare(strict_types=1);

use App\Auth\Models\User;
use App\Realms\Actions\CreateRealm;
use App\Realms\Actions\DeleteRealm;
use App\Realms\Models\Realm;
use App\Shared\Realms\Support\RealmConfiguration;
use Bambamboole\LaravelOidc\Server\SigningKeys\Models\SigningKey;
use Illuminate\Database\QueryException;

use function Tests\Helpers\realmRoute;

test('a new realm gets its own signing key and serves it from its own JWKS', function () {
    $realm = app(CreateRealm::class)->handle('Test Realm', 'test.lock.test', 'test-realm');

    $kid = SigningKey::query()->inRealm($realm->slug)->sole()->kid;

    $this->getJson(realmRoute($realm, 'oidc.jwks'))
        ->assertOk()
        ->assertJsonCount(1, 'keys')
        ->assertJsonPath('keys.0.kid', $kid);
});

test('a realm identifier cannot change once created', function () {
    $realm = Realm::factory()->create(['slug' => 'acme']);

    expect(fn () => $realm->update(['slug' => 'renamed']))->toThrow(LogicException::class);
});

test('an address is unique inside a realm and free in every other', function () {
    $realm = Realm::factory()->create();
    User::factory()->for($realm)->create(['email' => 'ada@example.com']);

    expect(fn () => User::factory()->for($realm)->create(['email' => 'ada@example.com']))->toThrow(QueryException::class)
        ->and(User::factory()->for(Realm::factory()->create())->create(['email' => 'ada@example.com'])->exists)->toBeTrue();
});

test('the admin realm cannot be deleted', function () {
    expect(fn () => app(DeleteRealm::class)->handle(Realm::master()))->toThrow(LogicException::class);

    $this->assertDatabaseHas('realms', ['slug' => Realm::master()->slug]);
});

it('stores only what a realm moves away from the instance defaults', function () {
    $realm = Realm::factory()->create();

    expect($realm->settings->accessTokenLifetime)->toBe(RealmConfiguration::defaults()['access_token_lifetime'])
        ->and($realm->getRawOriginal('settings'))->toBe('{}');

    $realm->writeSettings(['totp_window' => 4]);

    expect(json_decode((string) $realm->refresh()->getRawOriginal('settings'), true))->toBe(['totp_window' => 4]);

    $realm->writeSettings(['totp_window' => RealmConfiguration::defaults()['totp_window']]);

    expect($realm->refresh()->getRawOriginal('settings'))->toBe('[]');
});

it('normalises a written value to the type its setting holds', function () {
    $realm = Realm::factory()->create();

    $realm->writeSettings(['totp_window' => '4', 'password_numbers' => 1, 'default_scopes' => ['openid']]);

    expect($realm->refresh()->settings->totpWindow)->toBe(4)
        ->and($realm->settings->passwordNumbers)->toBeTrue()
        ->and($realm->settings->defaultScopes)->toBe(['openid']);
});

it('refuses to change a slug and to delete the administration realm', function () {
    $realm = Realm::factory()->create(['slug' => 'acme']);

    expect(fn () => $realm->update(['slug' => 'other']))->toThrow(LogicException::class)
        ->and(fn () => Realm::master()->delete())->toThrow(LogicException::class);
});

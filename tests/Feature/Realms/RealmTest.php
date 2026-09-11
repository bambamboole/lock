<?php
declare(strict_types=1);

use App\Auth\Models\User;
use App\Realms\Actions\CreateRealm;
use App\Realms\Actions\DeleteRealm;
use App\Realms\Models\Realm;
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

<?php
declare(strict_types=1);

use App\Auth\Models\User;
use App\Realms\Actions\CreateRealm;
use App\Realms\Actions\DeleteRealm;
use App\Realms\Models\Realm;
use Bambamboole\LaravelOidc\Server\SigningKeys\Models\SigningKey;
use Illuminate\Database\QueryException;

use function Tests\Helpers\realmRoute;

test('realms can be created', function () {
    $realm = app(CreateRealm::class)->handle('Test Realm', 'Test.Example.com');

    $this->assertDatabaseHas('realms', ['id' => $realm->id, 'name' => 'Test Realm', 'domain' => 'test.example.com', 'domain_status' => 'pending']);
});

test('a new realm gets its own signing key and serves it from its own JWKS', function () {
    $realm = app(CreateRealm::class)->handle('Test Realm', 'test.lock.test');

    $kid = SigningKey::query()->inRealm($realm->slug)->sole()->kid;

    $this->getJson(realmRoute($realm, 'oidc.jwks'))
        ->assertOk()
        ->assertJsonCount(1, 'keys')
        ->assertJsonPath('keys.0.kid', $kid);
});

test('realm slug uses next available suffix', function () {
    Realm::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
    Realm::factory()->create(['name' => 'Acme One', 'slug' => 'acme-1']);
    Realm::factory()->create(['name' => 'Acme Ten', 'slug' => 'acme-10']);

    app(CreateRealm::class)->handle('Acme', 'acme-11.lock.test');

    $this->assertDatabaseHas('realms', ['name' => 'Acme', 'slug' => 'acme-11']);
});

test('a realm identifier cannot change once created', function () {
    $realm = Realm::factory()->create(['slug' => 'acme']);

    expect(fn () => $realm->update(['slug' => 'renamed']))->toThrow(LogicException::class);
});

test('an address identifies a different person in each realm', function () {
    $one = Realm::factory()->create();
    $two = Realm::factory()->create();

    $here = User::factory()->for($one)->create(['email' => 'ada@example.com']);
    $there = User::factory()->for($two)->create(['email' => 'ada@example.com']);

    expect($here->is($there))->toBeFalse()
        ->and($here->realm_id)->not->toEqual($there->realm_id);
});

test('an address cannot repeat inside one realm', function () {
    $realm = Realm::factory()->create();

    User::factory()->for($realm)->create(['email' => 'ada@example.com']);

    expect(fn () => User::factory()->for($realm)->create(['email' => 'ada@example.com']))
        ->toThrow(QueryException::class);
});

test('the admin realm cannot be deleted', function () {
    expect(fn () => app(DeleteRealm::class)->handle(Realm::master()))->toThrow(LogicException::class);

    $this->assertDatabaseHas('realms', ['slug' => Realm::master()->slug]);
});

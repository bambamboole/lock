<?php
declare(strict_types=1);

use App\Auth\Models\User;
use App\Realms\Actions\CreateRealm;
use App\Realms\Actions\DeleteRealm;
use App\Realms\Models\Realm;
use Illuminate\Database\QueryException;

test('realms can be created', function () {
    $realm = app(CreateRealm::class)->handle('Test Realm');

    $this->assertDatabaseHas('realms', ['id' => $realm->id, 'name' => 'Test Realm']);
});

test('realm slug uses next available suffix', function () {
    Realm::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
    Realm::factory()->create(['name' => 'Acme One', 'slug' => 'acme-1']);
    Realm::factory()->create(['name' => 'Acme Ten', 'slug' => 'acme-10']);

    app(CreateRealm::class)->handle('Acme');

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

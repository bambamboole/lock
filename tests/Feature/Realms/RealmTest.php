<?php
declare(strict_types=1);

use App\Auth\Models\User;
use App\Realms\Actions\CreateRealm;
use App\Realms\Enums\RolePreset;
use App\Realms\Models\Realm;
use App\Realms\Ui\Forms\DeleteRealmForm;
use App\Realms\Ui\Forms\UpdateRealmForm;
use Illuminate\Database\QueryException;

use function Tests\Helpers\realmMember;

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

test('realms can be updated by owners', function () {
    [$user, $realm] = realmMember();

    $this->actingAs($user)
        ->submitForm(UpdateRealmForm::class, ['name' => 'Updated Name'], ['realm' => $realm->slug])
        ->assertRedirect(route('realm.settings.edit'));

    $this->assertDatabaseHas('realms', ['id' => $realm->id, 'name' => 'Updated Name']);
});

test('realms cannot be updated by members', function () {
    [, $realm] = realmMember();
    [$member] = realmMember(RolePreset::Member, $realm);

    $this->actingAs($member)
        ->submitDeniedForm(UpdateRealmForm::class, ['name' => 'Updated Name'], ['realm' => $realm->slug])
        ->assertForbidden();
});

test('deleting a realm deletes the users that lived in it', function () {
    [$user, $realm] = realmMember();
    [$member] = realmMember(RolePreset::Member, $realm);

    $this->actingAs($user)
        ->submitForm(DeleteRealmForm::class, ['name' => $realm->name], ['realm' => $realm->slug])
        ->assertRedirect();

    $this->assertSoftDeleted('realms', ['id' => $realm->id]);
    $this->assertDatabaseMissing('users', ['id' => $member->id]);
    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

test('realm deletion requires name confirmation', function () {
    [$user, $realm] = realmMember();

    $this->actingAs($user)
        ->submitForm(DeleteRealmForm::class, ['name' => 'Wrong Name'], ['realm' => $realm->slug])
        ->assertSessionHasErrors('name');

    $this->assertDatabaseHas('realms', ['id' => $realm->id, 'deleted_at' => null]);
});

test('the master realm cannot be deleted', function () {
    $master = Realm::master();
    [$user] = realmMember(realm: $master);

    $this->actingAs($user)
        ->submitDeniedForm(DeleteRealmForm::class, ['name' => $master->name], ['realm' => $master->slug])
        ->assertForbidden();

    $this->assertDatabaseHas('realms', ['id' => $master->id, 'deleted_at' => null]);
});

test('realms cannot be deleted by non owners', function () {
    [, $realm] = realmMember();
    [$member] = realmMember(RolePreset::Member, $realm);

    $this->actingAs($member)
        ->submitDeniedForm(DeleteRealmForm::class, ['name' => $realm->name], ['realm' => $realm->slug])
        ->assertForbidden();
});

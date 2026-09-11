<?php
declare(strict_types=1);

use App\Auth\Models\User;
use App\Realms\Models\Realm;

use function Tests\Helpers\realmRoute;

test('new users can register', function () {
    $realm = Realm::factory()->create();
    $response = $this->post(realmRoute($realm, 'identity.register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'test@example.com')->firstOrFail();

    $this->assertAuthenticatedAs($user, 'identity');
    $this->assertGuest('web');
    expect($user->name)->toBe('Test User')
        ->and($user->realm_id)->toBe($realm->id);
    $response->assertRedirect(realmRoute($realm, 'account'));
});

test('registration lands in the realm the request path names', function () {
    $acme = Realm::factory()->create(['slug' => 'acme']);

    $this->post(realmRoute($acme, 'identity.register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect(User::where('email', 'test@example.com')->firstOrFail()->realm_id)->toBe($acme->id);
});

test('the same address can register in two realms', function () {
    $acme = Realm::factory()->create(['slug' => 'acme']);
    $globex = Realm::factory()->create(['slug' => 'globex']);

    foreach ([$acme, $globex] as $realm) {
        auth()->forgetGuards();
        $this->post(realmRoute($realm, 'identity.register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasNoErrors();
    }

    expect(User::where('email', 'test@example.com')->pluck('realm_id')->all())
        ->toEqualCanonicalizing([$acme->id, $globex->id]);
});

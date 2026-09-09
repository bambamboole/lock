<?php
declare(strict_types=1);

use App\Auth\Models\User;
use App\Realms\Models\Realm;

test('new users can register', function () {
    $response = $this->post(route('identity.register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'test@example.com')->firstOrFail();

    $this->assertAuthenticatedAs($user, 'identity');
    $this->assertGuest('web');
    expect($user->name)->toBe('Test User')
        ->and($user->realm->slug)->toBe(config('realm.default_slug'));
    $response->assertRedirect('/login');
});

test('registration lands in the realm the request resolved', function () {
    $acme = Realm::factory()->create(['slug' => 'acme']);

    $acme->runAsCurrent(fn () => $this->post(route('identity.register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]));

    expect(User::where('email', 'test@example.com')->firstOrFail()->realm_id)->toBe($acme->id);
});

test('the same address can register in two realms', function () {
    $acme = Realm::factory()->create(['slug' => 'acme']);
    $globex = Realm::factory()->create(['slug' => 'globex']);

    foreach ([$acme, $globex] as $realm) {
        auth()->forgetGuards();
        $realm->runAsCurrent(fn () => $this->post(route('identity.register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasNoErrors());
    }

    expect(User::where('email', 'test@example.com')->pluck('realm_id')->all())
        ->toEqualCanonicalizing([$acme->id, $globex->id]);
});

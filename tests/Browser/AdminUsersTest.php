<?php

declare(strict_types=1);

use App\Auth\Models\User;
use App\Realms\Models\Realm;

use function Tests\Helpers\globalAdmin;

beforeEach(function () {
    $this->target = User::factory()->for(Realm::factory()->create(['slug' => 'acme']))->create(['name' => 'Wanda Needle', 'email' => 'wanda@acme.test']);
    $this->actingAs(globalAdmin());
});

it('shows each user of the realm by name and address', function () {
    visit('/admin/realms/acme/users')
        ->assertSee('Wanda Needle')
        ->assertSee('wanda@acme.test')
        ->assertNoJavaScriptErrors();
});

it('blocks a user from their detail page', function () {
    visit("/admin/realms/acme/users/{$this->target->id}")
        ->click('Block user')
        ->click('[data-test="confirm-accept"]')
        ->assertSee('User blocked.')
        ->assertNoJavaScriptErrors();

    expect($this->target->refresh()->isBlocked())->toBeTrue();
});

it('blocks a user from the row menu of the users table', function () {
    visit('/admin/realms/acme/users')
        ->click('button[aria-label="More actions"]')
        ->click('Block user')
        ->click('[data-test="confirm-accept"]')
        ->assertSee('User blocked.')
        ->assertNoJavaScriptErrors();

    expect($this->target->refresh()->isBlocked())->toBeTrue();
});

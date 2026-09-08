<?php

declare(strict_types=1);

use App\Auth\Models\User;

it('reveals the edit form behind a profile row and keeps the others closed', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/profile');

    $page->assertMissing('input[name="name"]')
        ->assertMissing('input[name="current_password"]')
        ->click('[data-test="row-name"] button')
        ->assertVisible('input[name="name"]')
        ->assertMissing('input[name="current_password"]')
        ->assertNoJavaScriptErrors();
});

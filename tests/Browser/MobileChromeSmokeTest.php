<?php
declare(strict_types=1);

use App\Auth\Models\User;

it('renders the dashboard on a mobile viewport', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $page = visit('/dashboard')->on()->mobile();

    $page->assertSee('Dashboard')
        ->assertNoJavaScriptErrors();
});

it('renders the realm settings tabs as a select on a mobile viewport', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $page = visit("/console/{$user->realm->slug}/settings")->on()->mobile();

    $page->assertPresent('[data-test=tabs-select]')
        ->assertNoJavaScriptErrors();
});

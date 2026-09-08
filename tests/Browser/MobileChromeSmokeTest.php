<?php
declare(strict_types=1);

use App\Auth\Models\User;

it('renders the dashboard on a mobile viewport', function () {
    $user = User::factory()->create();
    $tenant = $user->currentTenant()->firstOrFail();

    $this->actingAs($user);

    $page = visit('/'.$tenant->slug.'/dashboard')->on()->mobile();

    $page->assertSee('Dashboard')
        ->assertNoJavaScriptErrors();
});

it('renders the tenant settings tabs as a select on a mobile viewport', function () {
    $user = User::factory()->create();
    $tenant = $user->currentTenant()->firstOrFail();

    $this->actingAs($user);

    $page = visit('/tenants/'.$tenant->slug.'/settings')->on()->mobile();

    $page->assertPresent('[data-test=tabs-select]')
        ->assertNoJavaScriptErrors();
});

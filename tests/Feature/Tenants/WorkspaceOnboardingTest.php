<?php

declare(strict_types=1);

use App\Auth\Models\User;
use App\Tenants\Models\Tenant;

beforeEach(function () {
    $user = User::factory()->create();
    Tenant::query()->whereIn('id', $user->tenants()->pluck('tenants.id'))->get()->each->delete();
    $user->forceFill(['current_tenant_id' => null])->save();

    $this->userWithoutTenant = $user->refresh();
});

test('the dashboard landing sends users without a workspace to onboarding', function () {
    $this->actingAs($this->userWithoutTenant)
        ->get(route('dashboard.home'))
        ->assertRedirect(route('tenants.onboarding'));
});

test('the onboarding page renders the create workspace form', function () {
    $this->actingAs($this->userWithoutTenant)
        ->get(route('tenants.onboarding'))
        ->assertOk()
        ->assertSee(__('tenant.onboarding.heading'));
});

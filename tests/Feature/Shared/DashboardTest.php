<?php
declare(strict_types=1);

use App\Auth\Models\User;

test('guests are redirected to the login page', function () {
    $user = User::factory()->create();
    $tenant = $user->currentTenant;

    $response = $this->get(route('dashboard.home', ['current_tenant' => $tenant]));
    $response->assertRedirect(route('login'));
});

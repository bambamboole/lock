<?php

declare(strict_types=1);

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Auth\Models\User;
use App\Realms\Models\Realm;

use function Tests\Helpers\globalAdmin;

it('shows each user of the realm by name and address', function () {
    (new GlobalRoleSeeder)->run();
    User::factory()->for(Realm::factory()->create(['slug' => 'acme']))->create(['name' => 'Wanda Needle', 'email' => 'wanda@acme.test']);
    $this->actingAs(globalAdmin());

    visit('/admin/realms/acme/users')
        ->assertSee('Wanda Needle')
        ->assertSee('wanda@acme.test')
        ->assertNoJavaScriptErrors();
});

<?php
declare(strict_types=1);

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Auth\Models\User;
use App\Realms\Models\Realm;

use function Tests\Helpers\globalAdmin;
use function Tests\Helpers\visitAccountAs;

it('renders the account page on a mobile viewport', function () {
    $page = visitAccountAs(User::factory()->create())->on()->mobile();

    $page->assertSee('Account')
        ->assertNoJavaScriptErrors();
});

it('renders the realm tabs as a select on a mobile viewport', function () {
    (new GlobalRoleSeeder)->run();
    $realm = Realm::factory()->create(['slug' => 'acme']);
    $this->actingAs(globalAdmin());

    $page = visit("/admin/realms/{$realm->slug}/settings")->on()->mobile();

    $page->assertPresent('[data-test=tabs-select]')
        ->assertNoJavaScriptErrors();
});

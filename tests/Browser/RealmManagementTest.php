<?php

declare(strict_types=1);

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Realms\Models\Realm;

use function Tests\Helpers\globalAdmin;

it('creates a realm and saves its configuration from the admin interface', function () {
    (new GlobalRoleSeeder)->run();
    $this->actingAs(globalAdmin());

    visit('/admin/realms')
        ->click('Create realm')
        ->fill('input[name="name"]', 'Partner realm')
        ->fill('input[name="slug"]', 'partners')
        ->click('[data-lattice-form="admin.realms.create"]')
        ->assertSee('Realm created.')
        ->assertPathIs('/console/partners/settings')
        ->fill('input[name="session_cookie_name"]', 'partners-identity')
        ->fill('input[name="access_token_lifetime"]', '300')
        ->fill('textarea[name="allowed_redirect_domains"]', "partner.test\nlocalhost")
        ->click('[data-lattice-form="realms.configuration"]')
        ->assertSee('Realm configuration saved.')
        ->refresh()
        ->assertValue('input[name="session_cookie_name"]', 'partners-identity')
        ->assertValue('input[name="access_token_lifetime"]', '300')
        ->assertValue('textarea[name="allowed_redirect_domains"]', "partner.test\nlocalhost")
        ->assertNoJavaScriptErrors();

    $realm = Realm::query()->where('slug', 'partners')->sole();

    expect($realm->tokens()->accessTokenLifetime)->toBe(300)
        ->and($realm->sessions()->cookieName)->toBe('partners-identity')
        ->and($realm->clients()->allowedRedirectDomains)->toBe(['partner.test', 'localhost']);
});

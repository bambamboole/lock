<?php
declare(strict_types=1);

use App\Realms\Enums\RolePreset;
use App\Realms\Models\Realm;

use function Tests\Helpers\realmMember;

it('opens the create role form from the roles tab', function () {
    $realm = Realm::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
    [$user] = realmMember(RolePreset::Owner, realm: $realm);

    $this->actingAs($user);

    $page = visit('/settings/realm?tabs=roles');

    $page->assertMissing('input[name="name"]')
        ->click('[data-test="create-role"]')
        ->assertVisible('input[name="name"]')
        ->assertNoJavaScriptErrors();
});

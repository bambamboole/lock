<?php

declare(strict_types=1);

use App\Realms\Enums\RolePreset;
use App\Realms\Models\Realm;

use function Tests\Helpers\realmMember;
use function Tests\Helpers\renderedTabKeys;

it('renders every settings tab for an owner in priority order', function () {
    $realm = Realm::factory()->create();

    [$owner] = realmMember(RolePreset::Owner, realm: $realm);

    $response = $this->actingAs($owner)
        ->get(route('realm.settings.edit'))
        ->assertOk();

    expect(renderedTabKeys($response))->toBe(['general', 'members', 'roles', 'activity', 'danger']);
});

it('renders only the unrestricted tabs for a plain member', function () {
    $realm = Realm::factory()->create();

    [$member] = realmMember(RolePreset::Member, realm: $realm);

    $response = $this->actingAs($member)
        ->get(route('realm.settings.edit'))
        ->assertOk();

    expect(renderedTabKeys($response))->toBe(['members']);
});

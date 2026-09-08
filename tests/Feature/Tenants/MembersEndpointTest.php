<?php
declare(strict_types=1);

use App\Auth\Models\User;
use App\Tenants\Enums\RolePreset;
use App\Tenants\Models\Tenant;

use function Tests\Helpers\addMember;
use function Tests\Helpers\apiTokenFor;
use function Tests\Helpers\assumeNotNull;
use function Tests\Helpers\tenantMember;

beforeEach(fn () => $this->createPersonalAccessClient());

it('rejects a token that lacks the member:view scope', function () {
    [$owner, $tenant] = tenantMember(switch: false);
    // The scope check is literal: a manage-scoped token does not imply view.
    $plain = apiTokenFor($owner, $tenant, ['member:manage']);

    $this->withToken($plain)->getJson('/api/v1/members')->assertForbidden();
});

it('rejects when the role no longer grants the permission', function () {
    [$owner, $tenant] = tenantMember(switch: false);
    $plain = apiTokenFor($owner, $tenant, ['member:view']);

    // Downgrade after issuance: token keeps the scope, policy now denies.
    $owner->roles()->detach($tenant->ownerRole()->id);
    $owner->roles()->attach($tenant->memberRole()->id);

    $this->withToken($plain)->getJson('/api/v1/members')->assertForbidden();
});

it('returns the bound tenant members for an authorized token', function () {
    [$owner, $tenant] = tenantMember(switch: false);
    $other = User::factory()->create();
    addMember($tenant, $other, RolePreset::Member);

    $plain = apiTokenFor($owner, $tenant, ['member:view']);

    $response = $this->withToken($plain)->getJson('/api/v1/members')->assertOk();

    $ownerRole = $owner->rolesFor($tenant)->firstOrFail();
    $otherRole = $other->rolesFor($tenant)->firstOrFail();

    assumeNotNull($ownerRole->preset);
    assumeNotNull($otherRole->preset);

    $response->assertJsonCount(2, 'data');
    $response->assertJsonFragment(['email' => $owner->email, 'roles' => [['id' => $ownerRole->id, 'label' => $ownerRole->displayName(), 'preset' => $ownerRole->preset->value]]]);
    $response->assertJsonFragment(['email' => $other->email, 'roles' => [['id' => $otherRole->id, 'label' => $otherRole->displayName(), 'preset' => $otherRole->preset->value]]]);
});

it('isolates tenants: a token bound to tenant A never returns tenant B members', function () {
    [$owner, $tenantA] = tenantMember(switch: false);

    $tenantB = Tenant::factory()->create();
    $tenantBMember = User::factory()->create();
    addMember($tenantB, $tenantBMember, RolePreset::Owner);

    $plain = apiTokenFor($owner, $tenantA, ['member:view']);

    $this->withToken($plain)->getJson('/api/v1/members')
        ->assertOk()
        ->assertJsonMissing(['email' => $tenantBMember->email]);
});

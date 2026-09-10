<?php

declare(strict_types=1);

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Admin\Enums\AdminPermission;
use App\Realms\Enums\RolePreset;
use App\Realms\Models\Realm;
use App\Realms\Ui\Forms\UpdateRealmConfigurationForm;

use function Tests\Helpers\realmFormData;
use function Tests\Helpers\realmMember;

it('lets a realm owner configure their own realm but not another one', function () {
    [$owner, $realm] = realmMember(RolePreset::Owner);
    $other = Realm::factory()->create();

    $this->actingAs($owner)->submitForm(
        UpdateRealmConfigurationForm::class,
        realmFormData($realm, ['access_token_lifetime' => 300]),
        ['realm' => $realm->slug],
    )->assertRedirect();

    $this->submitDeniedForm(
        UpdateRealmConfigurationForm::class,
        realmFormData($other, ['access_token_lifetime' => 300]),
        ['realm' => $other->slug],
    )->assertNotFound();

    expect($realm->refresh()->tokens()->accessTokenLifetime)->toBe(300)
        ->and($other->refresh()->tokens()->accessTokenLifetime)->toBe(900);
});

it('hides the configuration from a realm member without the settings permission', function () {
    [$member, $realm] = realmMember(RolePreset::Admin);

    $this->actingAs($member)->submitDeniedForm(
        UpdateRealmConfigurationForm::class,
        realmFormData($realm, ['access_token_lifetime' => 300]),
        ['realm' => $realm->slug],
    )->assertForbidden();

    expect($realm->refresh()->tokens()->accessTokenLifetime)->toBe(900);
});

it('does not turn an ordinary master-realm owner into an instance administrator', function () {
    (new GlobalRoleSeeder)->run();
    [$owner] = realmMember(RolePreset::Owner, realm: Realm::master());

    expect($owner->can(AdminPermission::ManageRealms))->toBeFalse();
    $this->actingAs($owner)->get('/admin/realms')->assertForbidden();
});

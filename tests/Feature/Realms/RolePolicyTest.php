<?php
declare(strict_types=1);

use App\Realms\Enums\RealmPermission;
use App\Realms\Enums\RolePreset;

use function Tests\Helpers\realmMember;

it('lets owners manage roles implicitly and admins/members not', function () {
    [$owner, $realmO] = realmMember(RolePreset::Owner);
    [$admin, $realmA] = realmMember(RolePreset::Admin);
    [$member, $realmM] = realmMember(RolePreset::Member);

    expect($owner->can(RealmPermission::ManageRoles, $realmO))->toBeTrue()
        ->and($admin->can(RealmPermission::ManageRoles, $realmA))->toBeFalse()
        ->and($member->can(RealmPermission::ManageRoles, $realmM))->toBeFalse();
});

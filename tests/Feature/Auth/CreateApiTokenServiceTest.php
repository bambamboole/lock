<?php
declare(strict_types=1);

use App\Admin\Enums\AdminPermission;
use App\Auth\Models\OAuthAccessToken;
use App\Auth\Models\User;
use App\Auth\Support\GrantableTenantScopes;
use App\Tenants\Enums\RolePreset;
use App\Tenants\Models\Tenant;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function Tests\Helpers\apiTokenFor;
use function Tests\Helpers\tenantMember;

describe('CreateApiToken', function () {
    beforeEach(fn () => $this->createPersonalAccessClient());

    it('issues a tenant-bound token with the requested grantable scopes', function () {
        [$owner, $tenant] = tenantMember(switch: false);

        $plain = apiTokenFor($owner, $tenant, ['member:view']);

        expect($plain)->not->toBeEmpty();

        $token = OAuthAccessToken::query()->where('user_id', $owner->id)->firstOrFail();
        expect($token->tenant_id)->toBe($tenant->id)
            ->and($token->scopes)->toBe(['member:view']);
    });

    it('rejects scopes the role does not grant', function () {
        [$member, $tenant] = tenantMember(RolePreset::Member, switch: false);

        apiTokenFor($member, $tenant, ['member:view']);
    })->throws(ValidationException::class);

    it('aborts when the user is not a member of the tenant', function () {
        $outsider = User::factory()->create();
        $tenant = Tenant::factory()->create();

        apiTokenFor($outsider, $tenant, ['member:view']);
    })->throws(HttpException::class);

    it('refuses an admin scope requested for a tenant token', function () {
        [$owner, $tenant] = tenantMember(switch: false);

        apiTokenFor($owner, $tenant, ['member:view', AdminPermission::Access->value]);
    })->throws(ValidationException::class);
});

describe('GrantableTenantScopes', function () {
    it('narrows the offered scopes to what the role actually grants', function () {
        [$member, $tenant] = tenantMember(RolePreset::Member, switch: false);

        $role = $tenant->roles()->create(['name' => 'Member viewer']);
        $role->syncPermissions(['member:view']);
        $member->roles()->attach($role->id);

        expect(app(GrantableTenantScopes::class)->for($member, $tenant))->toBe(['member:view']);
    });

    it('lets a manage grant also offer the view scope', function () {
        [$admin, $tenant] = tenantMember(RolePreset::Admin, switch: false);

        $grantable = app(GrantableTenantScopes::class)->for($admin, $tenant);

        expect($grantable)->toEqualCanonicalizing(['member:view', 'member:manage']);
    });

    it('grants owners every catalog scope', function () {
        [$owner, $tenant] = tenantMember(switch: false);

        $grantable = app(GrantableTenantScopes::class)->for($owner, $tenant);

        expect($grantable)->toEqualCanonicalizing(['member:view', 'member:manage']);
    });
});

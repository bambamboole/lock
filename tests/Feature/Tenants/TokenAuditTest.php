<?php
declare(strict_types=1);

use App\Auth\Models\OAuthAccessToken;
use App\Auth\Ui\Actions\RevokeApiToken;
use App\Tenants\Enums\RolePreset;
use App\Tenants\Models\Activity;

use function Tests\Helpers\apiTokenFor;
use function Tests\Helpers\tenantMember;

beforeEach(fn () => $this->createPersonalAccessClient());

it('logs api-token.created', function () {
    [$user, $tenant] = tenantMember(RolePreset::Owner, switch: false);
    $this->actingAs($user);

    apiTokenFor($user, $tenant, ['member:view']);

    $a = Activity::query()->where('event', 'api-token.created')->latest('id')->firstOrFail();
    expect($a->tenant_id)->toBe($tenant->id)
        ->and($a->properties?->toArray())->toMatchArray(['name' => 'CLI']);
});

it('logs api-token.revoked', function () {
    [$user, $tenant] = tenantMember(RolePreset::Owner, switch: false);
    apiTokenFor($user, $tenant, ['member:view']);
    $token = OAuthAccessToken::query()->where('user_id', $user->id)->firstOrFail();

    $this->actingAs($user)
        ->callAction(RevokeApiToken::class, [], ['token_id' => $token->id]);

    expect(Activity::query()->where('event', 'api-token.revoked')->where('tenant_id', $tenant->id)->count())->toBe(1);
});

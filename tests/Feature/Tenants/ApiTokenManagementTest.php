<?php
declare(strict_types=1);

use App\Auth\Models\OAuthAccessToken;
use App\Auth\Models\User;
use App\Auth\Ui\Actions\RevokeApiToken;
use App\Auth\Ui\Forms\CreateApiTokenForm;
use App\Auth\Ui\Fragments\ApiTokenScopesFragment;
use App\Auth\Ui\Tables\ApiTokensTable;
use App\Tenants\Enums\RolePreset;

use function Tests\Helpers\apiTokenFor;
use function Tests\Helpers\tenantMember;

beforeEach(fn () => $this->createPersonalAccessClient());

it('creates a token through the form', function () {
    [$user, $tenant] = tenantMember(RolePreset::Owner);

    $this->actingAs($user)
        ->submitForm(CreateApiTokenForm::class, ['name' => 'My CLI token', 'scopes' => ['member:view']], ['tenant' => $tenant->slug])
        ->assertRedirect(route('tenant.settings.edit', ['current_tenant' => $tenant->slug, 'tabs' => 'api-tokens']));

    expect(OAuthAccessToken::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('revokes a token through the revoke action', function () {
    [$user, $tenant] = tenantMember(RolePreset::Owner, switch: false);

    apiTokenFor($user, $tenant, ['member:view']);
    $token = OAuthAccessToken::query()->where('user_id', $user->id)->firstOrFail();

    $this->actingAs($user)
        ->callAction(RevokeApiToken::class, [], ['token_id' => $token->id]);

    expect($token->refresh()->revoked)->toBeTrue();
});

it('filters tokens by name and scope through the global search', function () {
    [$user, $tenant] = tenantMember(RolePreset::Owner, switch: false);

    apiTokenFor($user, $tenant, ['member:manage'], 'Deploy key');
    apiTokenFor($user, $tenant, ['member:view'], 'Local CLI');

    $this->actingAs($user)
        ->loadTable(ApiTokensTable::class, ['q' => 'deploy'], ['tenant' => $tenant->slug])
        ->assertOk()
        ->assertSee('Deploy key')
        ->assertDontSee('Local CLI');

    $this->actingAs($user)
        ->loadTable(ApiTokensTable::class, ['q' => 'member:view'], ['tenant' => $tenant->slug])
        ->assertOk()
        ->assertSee('Local CLI')
        ->assertDontSee('Deploy key');
});

it('describes the token scopes in the lazy row detail fragment', function () {
    [$user, $tenant] = tenantMember(RolePreset::Owner, switch: false);

    apiTokenFor($user, $tenant, ['member:view']);
    $token = OAuthAccessToken::query()->where('user_id', $user->id)->firstOrFail();

    $this->actingAs($user)
        ->loadFragment(ApiTokenScopesFragment::class, ['token' => $token->id])
        ->assertOk()
        ->assertSee('member:view')
        ->assertSee('View tenant members and invitations');
});

it('does not reveal another user\'s token scopes through the fragment', function () {
    $userA = User::factory()->create();

    [$userB, $tenantB] = tenantMember(RolePreset::Owner, switch: false);

    apiTokenFor($userB, $tenantB, ['member:view'], 'B token');
    $userBToken = OAuthAccessToken::query()->where('user_id', $userB->id)->firstOrFail();

    $this->actingAs($userA)
        ->loadFragment(ApiTokenScopesFragment::class, ['token' => $userBToken->id])
        ->assertOk()
        ->assertDontSee('member:view')
        ->assertSee('This token has no scopes.');
});

it('prevents a user from revoking another user\'s API token', function () {
    $userA = User::factory()->create();

    [$userB, $tenantB] = tenantMember(RolePreset::Owner, switch: false);

    apiTokenFor($userB, $tenantB, ['member:view'], 'B token');
    $userBToken = OAuthAccessToken::query()->where('user_id', $userB->id)->firstOrFail();

    $this->actingAs($userA)
        ->callDeniedAction(RevokeApiToken::class, [], ['token_id' => $userBToken->id])
        ->assertForbidden();

    expect($userBToken->refresh()->revoked)->toBeFalse();
});

<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Auth\Actions\CreateApiToken;
use App\Auth\Models\User;
use App\Tenants\Models\Tenant;
use Laravel\Passkeys\Passkey;

function createPasskey(User $user, string $name = 'My passkey'): Passkey
{
    return $user->passkeys()->create([
        'name' => $name,
        'credential_id' => 'cred-'.fake()->unique()->uuid(),
        'credential' => ['type' => 'public-key'],
    ]);
}

/**
 * Issues a tenant-bound personal access token. Requires a personal access
 * client — see Tests\TestCase::createPersonalAccessClient().
 *
 * @param  array<int, string>  $scopes
 */
function apiTokenFor(User $user, Tenant $tenant, array $scopes, string $name = 'CLI'): string
{
    return app(CreateApiToken::class)->handle($user->refresh(), $tenant, $name, $scopes);
}

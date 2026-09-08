<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Auth\Models\User;
use Laravel\Passkeys\Passkey;

function createPasskey(User $user, string $name = 'My passkey'): Passkey
{
    return $user->passkeys()->create([
        'name' => $name,
        'credential_id' => 'cred-'.fake()->unique()->uuid(),
        'credential' => ['type' => 'public-key'],
    ]);
}

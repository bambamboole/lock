<?php

declare(strict_types=1);

namespace App\Auth\Support;

use App\Auth\Models\User;
use Bambamboole\LaravelOidc\Server\Scopes\Claims\ClaimSet;
use Bambamboole\LaravelOidc\Server\Scopes\Claims\ClaimsRequest;
use Bambamboole\LaravelOidc\Server\Scopes\Contracts\ClaimsResolver;

/**
 * The realm roles ride along as a flat `roles` list without a scope of their
 * own: they are part of the identity, so every audience sees them.
 */
final class UserClaimsResolver implements ClaimsResolver
{
    /** @return array<string, mixed> */
    public function resolve(ClaimsRequest $request): array
    {
        $user = $request->user;

        if (! $user instanceof User) {
            return [];
        }

        return [
            ...$this->scopedClaims($user, $request),
            RolesTokenClaim::CLAIM => $user->roleNames(),
        ];
    }

    /** @return array<string, mixed> */
    private function scopedClaims(User $user, ClaimsRequest $request): array
    {
        return new ClaimSet([
            'profile' => [
                'name' => $user->name,
                'locale' => $user->locale,
                'zoneinfo' => $user->timezone,
                'updated_at' => $user->updated_at?->getTimestamp(),
            ],
            'email' => [
                'email' => $user->email,
                'email_verified' => $user->hasVerifiedEmail(),
            ],
        ])->forScopes($request->scopes);
    }
}

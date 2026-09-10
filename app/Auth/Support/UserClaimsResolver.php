<?php

declare(strict_types=1);

namespace App\Auth\Support;

use App\Auth\Models\User;
use Bambamboole\LaravelOidc\Server\Scopes\Claims\ClaimSet;
use Bambamboole\LaravelOidc\Server\Scopes\Claims\ClaimsRequest;
use Bambamboole\LaravelOidc\Server\Scopes\Contracts\ClaimsResolver;

/**
 * The realm roles ride along as a flat `roles` list on every audience and
 * without a scope of their own: they are part of the identity, and the
 * relying party has to see them in the id_token as well as in userinfo.
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
            RealmRolesTokenClaim::CLAIM => $user->realmRoleNames(),
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

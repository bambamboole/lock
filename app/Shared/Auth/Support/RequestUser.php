<?php
declare(strict_types=1);

namespace App\Shared\Auth\Support;

use App\Auth\Models\User;
use Illuminate\Http\Request;

/**
 * The human behind a request, or null when there is none.
 *
 * Since laravel-oidc 0.27 a `client_credentials` token passes the `auth:oidc`
 * guard and resolves to a `ClientPrincipal`: a client acting for itself, with
 * no realm, no roles and no `can()`. Calling a gate on it is a fatal error
 * rather than a denial, so anything that means a person asks here first
 * instead of testing the principal's class at each call site.
 */
final class RequestUser
{
    public static function of(Request $request, ?string $guard = null): ?User
    {
        $user = $request->user($guard);

        return $user instanceof User ? $user : null;
    }

    public static function current(?string $guard = null): ?User
    {
        $user = auth()->guard($guard)->user();

        return $user instanceof User ? $user : null;
    }
}

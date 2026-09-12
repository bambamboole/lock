<?php
declare(strict_types=1);

namespace App\Auth\Actions;

use App\Auth\Models\User;
use App\Realms\Models\Realm;
use Bambamboole\LaravelOidc\Server\Brokering\Exceptions\SocialAuthenticationException;
use Bambamboole\LaravelOidc\Server\Shared\Brokering\CreateUserFromSocialAccount;
use Bambamboole\LaravelOidc\Server\Shared\Brokering\SocialUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Just-in-time provisioning for a brokered login, reached only while the
 * realm has `auto_provision` on. The controller turns a
 * SocialAuthenticationException into a failed sign-in, so refusing an
 * unusable upstream identity here reads as "we could not sign you in" rather
 * than a 500.
 */
final class CreateUserFromSocialLogin implements CreateUserFromSocialAccount
{
    public function __invoke(SocialUser $socialUser, string $provider): User
    {
        $realm = Realm::current();
        $email = $socialUser->email;

        if ($email === null) {
            throw new SocialAuthenticationException("The [{$provider}] identity carries no email address.");
        }

        /*
         * An unverified upstream address would let anyone who can name it
         * claim a realm identity, and the address is the realm's unique key.
         */
        if (! $socialUser->emailVerified) {
            throw new SocialAuthenticationException("The [{$provider}] identity's email address is not verified.");
        }

        // A password nobody knows: the account signs in through the upstream
        // provider, and a reset link gives it one of its own.
        return DB::transaction(function () use ($realm, $socialUser, $email): User {
            $user = User::create([
                'realm_id' => $realm->id,
                'name' => $socialUser->name ?? $socialUser->nickname ?? Str::before($email, '@'),
                'email' => $email,
                'password' => Str::password(32),
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();

            return $user;
        });
    }
}

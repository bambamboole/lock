<?php
declare(strict_types=1);

namespace App\Auth\Support;

use App\Auth\Models\User;
use Illuminate\Auth\Passwords\DatabaseTokenRepository;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Query\Builder;
use RuntimeException;

/**
 * `users` is unique per realm, so keyed by email alone one realm's reset
 * request would delete another's pending token, and a token minted for one
 * realm's user would validate for the other's.
 */
final class RealmTokenRepository extends DatabaseTokenRepository
{
    public function create(#[\SensitiveParameter] CanResetPassword $user)
    {
        $this->deleteExisting($user);

        $token = $this->createNewToken();

        $this->getTable()->insert([
            'realm_id' => $this->realmId($user),
            'email' => $user->getEmailForPasswordReset(),
            'token' => $this->hasher->make($token),
            'created_at' => now(),
        ]);

        return $token;
    }

    public function exists(CanResetPassword $user, #[\SensitiveParameter] $token)
    {
        $record = (array) $this->forUser($user)->first();

        return $record !== []
            && ! $this->tokenExpired($record['created_at'])
            && $this->hasher->check($token, $record['token']);
    }

    public function recentlyCreatedToken(CanResetPassword $user)
    {
        $record = (array) $this->forUser($user)->first();

        return $record !== [] && $this->tokenRecentlyCreated($record['created_at']);
    }

    protected function deleteExisting(CanResetPassword $user)
    {
        return $this->forUser($user)->delete();
    }

    private function forUser(CanResetPassword $user): Builder
    {
        return $this->getTable()
            ->where('realm_id', $this->realmId($user))
            ->where('email', $user->getEmailForPasswordReset());
    }

    private function realmId(CanResetPassword $user): string
    {
        if (! $user instanceof User) {
            throw new RuntimeException(sprintf(
                'Password resets are realm-scoped; %s carries no realm.',
                $user::class,
            ));
        }

        return $user->realm_id;
    }
}

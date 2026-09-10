<?php
declare(strict_types=1);

namespace App\Auth\Actions;

use App\Auth\Models\User;
use App\Shared\Auth\Contracts\DeletesUser;
use App\Shared\Auth\Contracts\EndsUserSessions;
use Bambamboole\LaravelOidc\Server\Authentication\Models\AuthenticationContext;
use Bambamboole\LaravelOidc\Server\Brokering\Models\SocialAccount;
use Bambamboole\LaravelOidc\Server\Consents\Models\Consent;
use Bambamboole\LaravelOidc\Server\Credentials\Models\PasswordHistory;
use Bambamboole\LaravelOidc\Server\Sessions\Models\OidcSession;
use Bambamboole\LaravelOidc\Server\Sessions\Models\SessionParticipant;
use Bambamboole\LaravelOidc\Server\Tokens\Models\AccessToken;
use Bambamboole\LaravelOidc\Server\Tokens\Models\AuthorizationCode;
use Bambamboole\LaravelOidc\Server\Tokens\Models\RefreshToken;
use Illuminate\Support\Facades\DB;

/**
 * The package rows that name a user carry no foreign key to `users`, so they
 * are removed here explicitly — the same way DeleteRealm removes the rows
 * keyed by a realm. Relying parties are told first, while the sessions still
 * exist to build a logout token from.
 */
final readonly class DeleteUser implements DeletesUser
{
    public function __construct(private EndsUserSessions $endSessions) {}

    public function handle(User $user): void
    {
        $this->endSessions->handle($user);

        DB::transaction(function () use ($user): void {
            $accessTokenIds = AccessToken::query()->where('user_id', $user->id)->pluck('id');

            RefreshToken::query()->whereIn('access_token_id', $accessTokenIds)->delete();
            AccessToken::query()->whereIn('id', $accessTokenIds)->delete();
            AuthorizationCode::query()->where('user_id', $user->id)->delete();
            Consent::query()->where('user_id', $user->id)->delete();
            AuthenticationContext::query()->where('user_id', $user->id)->delete();

            SessionParticipant::query()
                ->whereIn('sid', OidcSession::query()->where('user_id', $user->id)->select('sid'))
                ->delete();
            OidcSession::query()->where('user_id', $user->id)->delete();

            SocialAccount::query()->whereMorphedTo('authenticatable', $user)->delete();
            PasswordHistory::query()->whereMorphedTo('authenticatable', $user)->delete();

            $user->passkeys()->delete();
            $user->notifications()->delete();
            $user->roles()->detach();

            DB::table('password_reset_tokens')
                ->where('realm_id', $user->realm_id)
                ->where('email', $user->email)
                ->delete();

            $user->delete();
        });
    }
}

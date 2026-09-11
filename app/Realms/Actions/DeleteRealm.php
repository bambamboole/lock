<?php
declare(strict_types=1);

namespace App\Realms\Actions;

use App\Realms\Models\Realm;
use App\Shared\Audit\Audit;
use App\Shared\Realms\Contracts\DeletesRealm;
use App\Shared\Realms\Enums\RealmAuditEvent;
use Bambamboole\LaravelOidc\Server\Authentication\Models\AuthenticationContext;
use Bambamboole\LaravelOidc\Server\Brokering\Models\SocialAccount;
use Bambamboole\LaravelOidc\Server\Clients\Models\Client;
use Bambamboole\LaravelOidc\Server\Consents\Models\Consent;
use Bambamboole\LaravelOidc\Server\Sessions\Models\OidcSession;
use Bambamboole\LaravelOidc\Server\Sessions\Models\SessionParticipant;
use Bambamboole\LaravelOidc\Server\SigningKeys\Models\SigningKey;
use Bambamboole\LaravelOidc\Server\Tokens\Models\AccessToken;
use Bambamboole\LaravelOidc\Server\Tokens\Models\AuthorizationCode;
use Bambamboole\LaravelOidc\Server\Tokens\Models\RefreshToken;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class DeleteRealm implements DeletesRealm
{
    /**
     * Package rows carry the realm slug in `realm_id`; the app owns no
     * foreign key to them, so they are removed here explicitly.
     *
     * @var list<class-string<Model>>
     */
    private const array REALM_BOUND_PACKAGE_MODELS = [
        AuthorizationCode::class,
        RefreshToken::class,
        AccessToken::class,
        Consent::class,
        AuthenticationContext::class,
        SocialAccount::class,
        Client::class,
        SigningKey::class,
    ];

    /** The row goes for good, so a deleted realm's slug is free again. */
    public function handle(Realm $realm): void
    {
        DB::transaction(function () use ($realm): void {
            $realm->realmRoles()->delete();
            $realm->users()->each(fn ($user) => $user->delete());

            SessionParticipant::query()
                ->whereIn('sid', OidcSession::query()->where('realm_id', $realm->slug)->select('sid'))
                ->delete();
            OidcSession::query()->where('realm_id', $realm->slug)->delete();

            foreach (self::REALM_BOUND_PACKAGE_MODELS as $model) {
                $model::query()->where('realm_id', $realm->slug)->delete();
            }

            $realm->delete();

            Audit::record(RealmAuditEvent::RealmDeleted, $realm, properties: ['name' => $realm->name, 'slug' => $realm->slug]);
        });
    }
}

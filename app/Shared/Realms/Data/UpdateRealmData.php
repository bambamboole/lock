<?php
declare(strict_types=1);

namespace App\Shared\Realms\Data;

use App\Realms\Models\Realm;
use App\Shared\Data\ApiData;
use App\Shared\Realms\Support\RealmDomain;
use Bambamboole\Spectacular\Attributes\SpecProperty;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Optional;

/**
 * What a realm can be changed to. A slug is immutable, and everything left out
 * keeps its current value. Whether this realm may be re-pointed at all — the
 * master realm follows APP_URL — is the domain action's call, not this
 * payload's.
 */
final class UpdateRealmData extends ApiData
{
    public function __construct(
        #[SpecProperty('Human readable name shown to the realm’s users.')]
        #[Max(Realm::NAME_MAX_LENGTH)]
        public Optional|string $name,
        #[SpecProperty('The domain the realm is served from. Re-pointing it changes the issuer and restarts the domain check.')]
        #[Max(253), Regex(RealmDomain::PATTERN)]
        public Optional|string $domain,
        #[SpecProperty('Any subset of the realm’s policy; settings left out keep their current value.')]
        public Optional|RealmSettingsPatch $settings,
    ) {}

    /**
     * A host is case-insensitive, so the payload is normalised before the
     * pattern is checked — `Acme.Test` names the same realm as `acme.test`.
     *
     * @param  array<array-key, mixed>  $properties
     * @return array<array-key, mixed>
     */
    public static function prepareForPipeline(array $properties): array
    {
        if (is_string($properties['domain'] ?? null)) {
            $properties['domain'] = RealmDomain::normalize($properties['domain']);
        }

        return $properties;
    }
}

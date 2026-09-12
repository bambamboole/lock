<?php
declare(strict_types=1);

namespace App\Realms\Http\Api\V1\Resources;

use App\Realms\Models\Realm;
use App\Shared\Realms\Data\RealmSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Realm
 */
final class RealmResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            /** Stable identifier of the realm, and its path in this API. */
            'slug' => $this->slug,
            /** Human readable name shown to its users. */
            'name' => $this->name,
            /** The host the realm is served from. */
            'host' => $this->host(),
            /** The realm's OIDC issuer, the `iss` of every token it mints. */
            'issuer' => $this->origin(),
            /** The configured domain; null for the master realm, which follows APP_URL. */
            'domain' => $this->domain,
            /** Whether the configured domain was seen to reach this instance. */
            'domain_status' => $this->domain_status,
            /** When the domain was last checked. */
            'domain_checked_at' => $this->domain_checked_at?->toIso8601String(),
            /** Why the last domain check failed, when it did. */
            'domain_check_error' => $this->domain_check_error,
            /** Whether this is the administration realm, which cannot be deleted or re-pointed. */
            'master' => $this->isMaster(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'settings' => $this->settings(),
        ];
    }

    /** The realm's own policy, described by the DTO the column casts to. */
    private function settings(): RealmSettings
    {
        return $this->resource->settings;
    }
}

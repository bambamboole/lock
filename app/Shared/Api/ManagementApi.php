<?php
declare(strict_types=1);

namespace App\Shared\Api;

use App\Realms\Models\Realm;
use App\Resources\Models\Resource;
use App\Resources\Models\ResourceScope;
use App\Shared\Api\Enums\ApiScope;
use App\Shared\Audit\Audit;
use App\Shared\Console\Enums\BootstrapOutcome;
use App\Shared\Resources\Enums\ResourceAuditEvent;
use Bambamboole\LaravelOidc\Server\Shared\Tokens\RealmAudiences;
use Illuminate\Support\Facades\DB;

/**
 * Lock's own management API as an OAuth protected resource: a resource row of
 * the master realm carrying the scopes of {@see ApiScope}. The rows are real,
 * so the console lists them and a token request resolves them like any other
 * resource, but `app:deploy` owns them and the console refuses to change them.
 */
final readonly class ManagementApi
{
    /**
     * Relative to the issuer, so the audience follows APP_URL and RFC 9728
     * metadata is published under `.well-known/oauth-protected-resource/api`.
     */
    public const string RESOURCE = 'api';

    public const string NAME = 'Management API';

    public function __construct(private RealmAudiences $audiences) {}

    /**
     * The `aud` value a token for this API carries. Only resolvable while the
     * master realm is current, which every request to `api/*` is.
     */
    public function audience(): string
    {
        return $this->audiences->protectedResource(self::RESOURCE);
    }

    /** The resource row and its scopes, as the deploy leaves them. */
    public function reconcile(Realm $realm): BootstrapOutcome
    {
        return DB::transaction(function () use ($realm): BootstrapOutcome {
            $resource = $realm->realmResources()->firstOrNew(['identifier' => self::RESOURCE]);
            $created = ! $resource->exists;

            $resource->name = self::NAME;
            $changed = $resource->isDirty();
            $resource->save();

            if ($created) {
                Audit::record(ResourceAuditEvent::ResourceCreated, $resource, $realm, $this->context($realm));
            }

            $changed = $this->reconcileScopes($resource, $realm) || $changed;

            return match (true) {
                $created => BootstrapOutcome::Created,
                $changed => BootstrapOutcome::Updated,
                default => BootstrapOutcome::Unchanged,
            };
        });
    }

    /** `app:deploy` owns these rows, so nothing else may rename or delete them. */
    public function owns(Resource $resource): bool
    {
        return $resource->identifier === self::RESOURCE && $resource->realm->isMaster();
    }

    public function ownsScope(ResourceScope $scope): bool
    {
        return $this->owns($scope->resource);
    }

    private function reconcileScopes(Resource $resource, Realm $realm): bool
    {
        $changed = false;

        foreach (ApiScope::cases() as $scope) {
            $row = $resource->scopes()->firstOrNew(['value' => $scope->value]);
            $created = ! $row->exists;

            $row->description = $scope->description();

            if ($created || $row->isDirty()) {
                $row->save();
                $changed = true;

                Audit::record(
                    $created ? ResourceAuditEvent::ScopeCreated : ResourceAuditEvent::ScopeUpdated,
                    $row,
                    $realm,
                    [...$this->context($realm), 'value' => $row->value],
                );
            }
        }

        $removed = $resource->scopes()->whereNotIn('value', ApiScope::values())->get();

        foreach ($removed as $row) {
            $row->delete();

            Audit::record(ResourceAuditEvent::ScopeDeleted, $row, $realm, [...$this->context($realm), 'value' => $row->value]);
        }

        $resource->unsetRelation('scopes');

        return $changed || $removed->isNotEmpty();
    }

    /** @return array<string, string> */
    private function context(Realm $realm): array
    {
        return [
            'actor' => 'console:app:bootstrap',
            'realm' => $realm->slug,
            'resource' => self::RESOURCE,
        ];
    }
}

<?php
declare(strict_types=1);

namespace App\Resources\Actions;

use App\Resources\Models\Resource;
use App\Shared\Api\ManagementApi;
use App\Shared\Audit\Audit;
use App\Shared\Resources\Contracts\DeletesResource;
use App\Shared\Resources\Enums\ResourceAuditEvent;
use Illuminate\Support\Facades\DB;
use LogicException;

final readonly class DeleteResource implements DeletesResource
{
    public function __construct(private ManagementApi $api) {}

    public function handle(Resource $resource): void
    {
        $this->guardManagementApi($resource);

        DB::transaction(function () use ($resource): void {
            $scopes = $resource->scopes()->pluck('value')->all();

            $resource->scopes()->delete();
            $resource->delete();

            Audit::record(ResourceAuditEvent::ResourceDeleted, $resource, $resource->realm, [
                'realm' => $resource->realm->slug,
                'identifier' => $resource->identifier,
                'scopes-deleted' => count($scopes),
            ]);
        });
    }

    private function guardManagementApi(Resource $resource): void
    {
        if ($this->api->owns($resource)) {
            throw new LogicException('The management API resource is reconciled by app:deploy and cannot be changed here.');
        }
    }
}

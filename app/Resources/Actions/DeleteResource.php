<?php
declare(strict_types=1);

namespace App\Resources\Actions;

use App\Resources\Models\Resource;
use App\Shared\Audit\Audit;
use App\Shared\Resources\Contracts\DeletesResource;
use App\Shared\Resources\Enums\ResourceAuditEvent;
use Illuminate\Support\Facades\DB;

final class DeleteResource implements DeletesResource
{
    public function handle(Resource $resource): void
    {
        DB::transaction(function () use ($resource): void {
            $scopes = $resource->scopes()->pluck('value')->all();

            $resource->scopes()->delete();
            $resource->delete();

            Audit::record(ResourceAuditEvent::ResourceDeleted, $resource, properties: [
                'realm' => $resource->realm->slug,
                'identifier' => $resource->identifier,
                'scopes-deleted' => count($scopes),
            ]);
        });
    }
}

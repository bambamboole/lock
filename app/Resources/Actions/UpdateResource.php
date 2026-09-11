<?php
declare(strict_types=1);

namespace App\Resources\Actions;

use App\Resources\Models\Resource;
use App\Shared\Audit\Audit;
use App\Shared\Resources\Contracts\UpdatesResource;
use App\Shared\Resources\Enums\ResourceAuditEvent;
use Illuminate\Support\Facades\DB;

final class UpdateResource implements UpdatesResource
{
    public function handle(Resource $resource, string $identifier, string $name): void
    {
        DB::transaction(function () use ($resource, $identifier, $name): void {
            $before = $resource->only(['identifier', 'name']);

            $resource->fill(['identifier' => $identifier, 'name' => $name])->save();

            $changes = [];

            foreach ($resource->only(['identifier', 'name']) as $key => $value) {
                if ($before[$key] !== $value) {
                    $changes[$key] = ['old' => $before[$key], 'new' => $value];
                }
            }

            if ($changes !== []) {
                Audit::record(ResourceAuditEvent::ResourceUpdated, $resource, properties: [
                    'realm' => $resource->realm->slug,
                    'identifier' => $resource->identifier,
                    'changes' => $changes,
                ]);
            }
        });
    }
}

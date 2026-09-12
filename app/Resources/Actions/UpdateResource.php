<?php
declare(strict_types=1);

namespace App\Resources\Actions;

use App\Resources\Models\Resource;
use App\Shared\Api\ManagementApi;
use App\Shared\Audit\Audit;
use App\Shared\Resources\Contracts\UpdatesResource;
use App\Shared\Resources\Enums\ResourceAdminEvent;
use Illuminate\Support\Facades\DB;
use LogicException;

final readonly class UpdateResource implements UpdatesResource
{
    public function __construct(private ManagementApi $api) {}

    public function handle(Resource $resource, string $identifier, string $name): void
    {
        $this->guardManagementApi($resource);

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
                Audit::record(ResourceAdminEvent::ResourceUpdated, $resource, $resource->realm, [
                    'realm' => $resource->realm->slug,
                    'identifier' => $resource->identifier,
                    'changes' => $changes,
                ]);
            }
        });
    }

    private function guardManagementApi(Resource $resource): void
    {
        if ($this->api->owns($resource)) {
            throw new LogicException('The management API resource is reconciled by app:deploy and cannot be changed here.');
        }
    }
}

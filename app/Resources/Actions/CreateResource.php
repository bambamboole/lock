<?php
declare(strict_types=1);

namespace App\Resources\Actions;

use App\Realms\Models\Realm;
use App\Resources\Models\Resource;
use App\Shared\Audit\Audit;
use App\Shared\Resources\Contracts\CreatesResource;
use App\Shared\Resources\Enums\ResourceAuditEvent;
use Illuminate\Support\Facades\DB;

final class CreateResource implements CreatesResource
{
    public function handle(Realm $realm, string $identifier, string $name): Resource
    {
        return DB::transaction(function () use ($realm, $identifier, $name): Resource {
            $resource = $realm->realmResources()->create([
                'identifier' => $identifier,
                'name' => $name,
            ]);

            Audit::record(ResourceAuditEvent::ResourceCreated, $resource, properties: [
                'realm' => $realm->slug,
                'identifier' => $resource->identifier,
                'name' => $resource->name,
            ]);

            return $resource;
        });
    }
}

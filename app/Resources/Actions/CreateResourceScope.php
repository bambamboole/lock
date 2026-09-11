<?php
declare(strict_types=1);

namespace App\Resources\Actions;

use App\Resources\Models\Resource;
use App\Resources\Models\ResourceScope;
use App\Shared\Audit\Audit;
use App\Shared\Resources\Contracts\CreatesResourceScope;
use App\Shared\Resources\Enums\ResourceAuditEvent;
use Illuminate\Support\Facades\DB;

final class CreateResourceScope implements CreatesResourceScope
{
    public function handle(Resource $resource, string $value, ?string $description): ResourceScope
    {
        return DB::transaction(function () use ($resource, $value, $description): ResourceScope {
            $scope = $resource->scopes()->create([
                'value' => $value,
                'description' => $description,
            ]);

            Audit::record(ResourceAuditEvent::ScopeCreated, $scope, properties: [
                'realm' => $resource->realm->slug,
                'resource' => $resource->identifier,
                'value' => $scope->value,
            ]);

            return $scope;
        });
    }
}

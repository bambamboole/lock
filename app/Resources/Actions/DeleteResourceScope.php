<?php
declare(strict_types=1);

namespace App\Resources\Actions;

use App\Resources\Models\ResourceScope;
use App\Shared\Audit\Audit;
use App\Shared\Resources\Contracts\DeletesResourceScope;
use App\Shared\Resources\Enums\ResourceAuditEvent;
use Illuminate\Support\Facades\DB;

final class DeleteResourceScope implements DeletesResourceScope
{
    public function handle(ResourceScope $scope): void
    {
        DB::transaction(function () use ($scope): void {
            $resource = $scope->resource;

            $scope->delete();

            Audit::record(ResourceAuditEvent::ScopeDeleted, $scope, $resource->realm, [
                'realm' => $resource->realm->slug,
                'resource' => $resource->identifier,
                'value' => $scope->value,
            ]);
        });
    }
}

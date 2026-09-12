<?php
declare(strict_types=1);

namespace App\Resources\Actions;

use App\Resources\Models\ResourceScope;
use App\Shared\Api\ManagementApi;
use App\Shared\Audit\Audit;
use App\Shared\Resources\Contracts\DeletesResourceScope;
use App\Shared\Resources\Enums\ResourceAuditEvent;
use Illuminate\Support\Facades\DB;
use LogicException;

final readonly class DeleteResourceScope implements DeletesResourceScope
{
    public function __construct(private ManagementApi $api) {}

    public function handle(ResourceScope $scope): void
    {
        $this->guardManagementApi($scope);

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

    private function guardManagementApi(ResourceScope $scope): void
    {
        if ($this->api->ownsScope($scope)) {
            throw new LogicException('The management API resource is reconciled by app:deploy and cannot be changed here.');
        }
    }
}

<?php
declare(strict_types=1);

namespace App\Resources\Actions;

use App\Resources\Models\ResourceScope;
use App\Shared\Audit\Audit;
use App\Shared\Resources\Contracts\UpdatesResourceScope;
use App\Shared\Resources\Enums\ResourceAuditEvent;
use Illuminate\Support\Facades\DB;

final class UpdateResourceScope implements UpdatesResourceScope
{
    public function handle(ResourceScope $scope, string $value, ?string $description): void
    {
        DB::transaction(function () use ($scope, $value, $description): void {
            $before = $scope->only(['value', 'description']);

            $scope->fill(['value' => $value, 'description' => $description])->save();

            $changes = [];

            foreach ($scope->only(['value', 'description']) as $key => $current) {
                if ($before[$key] !== $current) {
                    $changes[$key] = ['old' => $before[$key], 'new' => $current];
                }
            }

            if ($changes !== []) {
                Audit::record(ResourceAuditEvent::ScopeUpdated, $scope, $scope->resource->realm, [
                    'realm' => $scope->resource->realm->slug,
                    'resource' => $scope->resource->identifier,
                    'value' => $scope->value,
                    'changes' => $changes,
                ]);
            }
        });
    }
}

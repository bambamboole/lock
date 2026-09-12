<?php
declare(strict_types=1);

namespace App\Resources\Actions;

use App\Resources\Models\Resource;
use App\Resources\Models\ResourceScope;
use App\Shared\Api\ManagementApi;
use App\Shared\Audit\Audit;
use App\Shared\Resources\Contracts\CreatesResourceScope;
use App\Shared\Resources\Data\CreateResourceScopeData;
use App\Shared\Resources\Enums\ResourceAdminEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use LogicException;

final readonly class CreateResourceScope implements CreatesResourceScope
{
    public function __construct(private ManagementApi $api) {}

    public function handle(Resource $resource, CreateResourceScopeData $data): ResourceScope
    {
        if ($this->api->owns($resource)) {
            throw new LogicException('The management API resource is reconciled by app:deploy and cannot be changed here.');
        }

        return DB::transaction(function () use ($resource, $data): ResourceScope {
            Validator::make(['value' => $data->value], [
                'value' => [Rule::unique(ResourceScope::class, 'value')->where('resource_id', $resource->id)],
            ])->validate();

            $scope = $resource->scopes()->create($data->toAttributes());

            Audit::record(ResourceAdminEvent::ScopeCreated, $scope, $resource->realm, [
                'realm' => $resource->realm->slug,
                'resource' => $resource->identifier,
                'value' => $scope->value,
            ]);

            return $scope;
        });
    }
}

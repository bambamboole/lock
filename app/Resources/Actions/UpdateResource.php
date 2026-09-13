<?php
declare(strict_types=1);

namespace App\Resources\Actions;

use App\Resources\Models\Resource;
use App\Shared\Api\ManagementApi;
use App\Shared\Audit\Audit;
use App\Shared\Resources\Contracts\UpdatesResource;
use App\Shared\Resources\Data\UpdateResourceData;
use App\Shared\Resources\Enums\ResourceAdminEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use LogicException;
use Spatie\LaravelData\Optional;

final readonly class UpdateResource implements UpdatesResource
{
    public function __construct(private ManagementApi $api, private SyncResourceScopes $syncScopes) {}

    public function handle(Resource $resource, UpdateResourceData $data): void
    {
        $this->guardManagementApi($resource);

        $attributes = $data->toAttributes(only: ['identifier', 'name']);

        if (! $data->identifier instanceof Optional) {
            $attributes['identifier'] = trim($data->identifier);
        }

        UpdateResourceData::validate($attributes);
        Validator::make($attributes, [
            'identifier' => [Rule::unique(Resource::class, 'identifier')->where('realm_id', $resource->realm_id)->ignore($resource)],
        ])->validate();

        DB::transaction(function () use ($resource, $attributes, $data): void {
            if (! $data->scopes instanceof Optional) {
                $this->syncScopes->handle($resource, $data->scopes);
            }

            $before = $resource->only(['identifier', 'name']);

            $resource->fill($attributes)->save();

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

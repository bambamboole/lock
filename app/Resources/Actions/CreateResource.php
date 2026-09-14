<?php
declare(strict_types=1);

namespace App\Resources\Actions;

use App\Realms\Models\Realm;
use App\Resources\Models\Resource;
use App\Shared\Audit\Audit;
use App\Shared\Resources\Contracts\CreatesResource;
use App\Shared\Resources\Data\CreateResourceData;
use App\Shared\Resources\Enums\ResourceAdminEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final readonly class CreateResource implements CreatesResource
{
    public function __construct(private SyncResourceScopes $syncScopes) {}

    public function handle(Realm $realm, CreateResourceData $data): Resource
    {
        $attributes = $data->toAttributes(only: ['identifier', 'name']);
        $attributes['identifier'] = trim($data->identifier);
        CreateResourceData::validate($attributes);
        Validator::make($attributes, [
            'identifier' => [Rule::unique(Resource::class, 'identifier')->where('realm_id', $realm->id)],
        ])->validate();

        return DB::transaction(function () use ($realm, $attributes, $data): Resource {
            $resource = $realm->realmResources()->create($attributes);

            Audit::record(ResourceAdminEvent::ResourceCreated, $resource, $realm, [
                'realm' => $realm->slug,
                'identifier' => $resource->identifier,
                'name' => $resource->name,
            ]);

            $this->syncScopes->handle($resource, $data->scopes);

            return $resource;
        });
    }
}

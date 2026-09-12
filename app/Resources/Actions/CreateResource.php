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

final class CreateResource implements CreatesResource
{
    public function handle(Realm $realm, CreateResourceData $data): Resource
    {
        $attributes = $data->toAttributes();
        $attributes['identifier'] = trim($data->identifier);
        CreateResourceData::validate($attributes);
        Validator::make($attributes, [
            'identifier' => [Rule::unique(Resource::class, 'identifier')->where('realm_id', $realm->id)],
        ])->validate();

        return DB::transaction(function () use ($realm, $attributes): Resource {
            $resource = $realm->realmResources()->create($attributes);

            Audit::record(ResourceAdminEvent::ResourceCreated, $resource, $realm, [
                'realm' => $realm->slug,
                'identifier' => $resource->identifier,
                'name' => $resource->name,
            ]);

            return $resource;
        });
    }
}

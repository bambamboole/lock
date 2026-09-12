<?php
declare(strict_types=1);

namespace App\Admin\Ui\Concerns;

use App\Realms\Models\Realm;
use App\Realms\Models\RealmRole;
use App\Resources\Models\ResourceScope;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Lattice\Form\Components\CheckboxGroup;
use Lattice\Form\Components\Field;
use Lattice\Form\Components\TextInput;

trait BuildsRealmRoleFields
{
    /**
     * @return array<int, Field>
     */
    private function realmRoleFields(Realm $realm, ?RealmRole $role): array
    {
        $scopes = $this->selectableScopes($realm);

        return [
            ...$this->realmRoleNameFields($realm, $role),
            CheckboxGroup::make('scope_ids', __('admin.roles.fields.scopes.label'))
                ->helperText(__('admin.roles.fields.scopes.help-text'))
                ->options($scopes->map(fn (ResourceScope $scope) => CheckboxGroup::option(
                    $scope->resource->identifier.' · '.$scope->value,
                    $scope->id,
                ))->all())
                ->value($role?->scopes()->pluck('resource_scopes.id')->all() ?? [], editable: true)
                ->rules(['array', Rule::in($scopes->modelKeys())]),
        ];
    }

    /**
     * The name lands verbatim in the `roles` claim of a JWT, so it is kept to
     * a token-safe alphabet.
     *
     * @return array<int, TextInput>
     */
    private function realmRoleNameFields(Realm $realm, ?RealmRole $role): array
    {
        $unique = Rule::unique(RealmRole::class, 'name')->where('realm_id', $realm->id);

        return [
            TextInput::make('name', __('admin.roles.fields.name.label'))
                ->value($role?->name, editable: true)
                ->required()
                ->helperText(__('admin.roles.fields.name.help-text'))
                ->rules(['string', 'max:64', 'regex:/^[A-Za-z0-9][A-Za-z0-9_.:-]*$/', $role instanceof RealmRole ? $unique->ignore($role) : $unique])
                ->message('regex', __('admin.roles.validation.name-format')),
            TextInput::make('description', __('admin.roles.fields.description.label'))
                ->value($role?->description, editable: true)
                ->rules(['nullable', 'string', 'max:255']),
        ];
    }

    /**
     * @return Collection<int, ResourceScope>
     */
    private function selectableScopes(Realm $realm): Collection
    {
        return ResourceScope::query()
            ->with('resource')
            ->whereHas('resource', fn ($query) => $query->where('realm_id', $realm->id))
            ->join('resources', 'resources.id', '=', 'resource_scopes.resource_id')
            ->orderBy('resources.identifier')
            ->orderBy('resource_scopes.value')
            ->select('resource_scopes.*')
            ->get();
    }

    /**
     * @return list<string>
     */
    private function submittedScopeIds(Realm $realm, mixed $submitted): array
    {
        $allowed = $this->selectableScopes($realm)->modelKeys();

        return array_values(array_intersect(array_map(strval(...), (array) $submitted), $allowed));
    }
}

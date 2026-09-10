<?php
declare(strict_types=1);

namespace App\Admin\Ui\Concerns;

use App\Realms\Models\Realm;
use App\Realms\Models\RealmRole;
use Illuminate\Validation\Rule;
use Lattice\Form\Components\TextInput;

trait BuildsRealmRoleFields
{
    /**
     * The name lands verbatim in the `roles` claim of a JWT, so it is kept to
     * a token-safe alphabet.
     *
     * @return array<int, TextInput>
     */
    private function realmRoleFields(Realm $realm, ?RealmRole $role): array
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
}

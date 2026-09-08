<?php
declare(strict_types=1);

namespace App\Realms\Ui\Forms;

use App\Auth\Support\PermissionDefinition;
use App\Auth\Support\PermissionRepository;
use App\Realms\Enums\RealmAuditEvent;
use App\Realms\Enums\RealmPermission;
use App\Realms\Models\Realm;
use App\Realms\Models\Role;
use App\Realms\Support\Audit;
use App\Realms\Ui\Concerns\ActivatesRealmContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Lattice\Facades\Effects;
use Lattice\Form\Attributes\AsForm;
use Lattice\Form\Components\CheckboxGroup;
use Lattice\Form\Components\Form as FormComponent;
use Lattice\Form\Components\TextInput;
use Lattice\Form\FormData;
use Lattice\Form\FormDefinition;
use Lattice\Http\LatticeResponse;
use Lattice\Ui\Components\Grid;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsForm('realms.roles.form', can: RealmPermission::ManageRoles, on: 'realm')]
final class RoleForm extends FormDefinition
{
    use ActivatesRealmContext;

    public function __construct(private readonly PermissionRepository $permissions) {}

    public function definition(FormComponent $form, Request $request): FormComponent
    {
        /** @var Realm $realm */
        $realm = $this->contextModel('realm');
        /** @var Role|null $role */
        $role = $this->hasContext('role') ? $this->contextModel('role') : null;

        $uniqueName = Rule::unique('roles', 'name')->where('realm_id', $realm->id);

        if ($role instanceof Role) {
            $uniqueName = $uniqueName->ignore($role->id);
        }

        $permissionOptions = array_map(
            fn (PermissionDefinition $permission) => CheckboxGroup::option(
                __($permission->descriptionKey),
                $permission->value,
                description: $permission->value,
                group: __($permission->group),
            ),
            $this->permissions->realm(),
        );

        return $form
            ->method(HttpMethod::Post)
            ->schema([
                Grid::make('realms-roles-fields')
                    ->columns(1)
                    ->schema([
                        TextInput::make('name', __('common.field.name'))
                            ->placeholder(__('realm.settings.roles.name.placeholder'))
                            ->required()
                            ->rules(['string', 'max:255', $uniqueName]),
                        CheckboxGroup::make('permissions', __('realm.settings.roles.permissions'))
                            ->options($permissionOptions)
                            ->bulkToggleable()
                            ->helperText(__('realm.settings.roles.permissions-help')),
                    ]),
            ])
            ->submitLabel($role instanceof Role ? __('realm.settings.roles.save') : __('realm.settings.roles.create.label'));
    }

    public function handle(FormData $data): LatticeResponse
    {
        /** @var Realm $realm */
        $realm = $this->contextModel('realm');
        /** @var Role|null $role */
        $role = $this->hasContext('role') ? $this->contextModel('role') : null;

        abort_if($role?->isOwner() === true, 403);

        $permissions = array_values((array) ($data['permissions'] ?? []));

        $event = $role instanceof Role ? 'updated' : 'created';

        $role = DB::transaction(function () use ($realm, $role, $data, $permissions): Role {
            if ($role instanceof Role) {
                $role->update(['name' => (string) $data['name']]);
            } else {
                $role = $realm->roles()->create(['name' => (string) $data['name']]);
            }

            $role->syncPermissions($permissions);

            return $role;
        });

        Audit::record($event === 'created' ? RealmAuditEvent::RoleCreated : RealmAuditEvent::RoleUpdated, $role, $realm, [
            'name' => $role->name,
        ]);

        return Effects::respond()->toast(__("realm.settings.roles.{$event}"), Variant::Success)
            ->toRoute('realm.settings.edit');
    }
}

<?php
declare(strict_types=1);

namespace App\Tenants\Ui\Forms;

use App\Auth\Support\PermissionDefinition;
use App\Auth\Support\PermissionRepository;
use App\Tenants\Enums\TenantAuditEvent;
use App\Tenants\Enums\TenantPermission;
use App\Tenants\Models\Role;
use App\Tenants\Models\Tenant;
use App\Tenants\Support\Audit;
use App\Tenants\Ui\Concerns\ActivatesTenantContext;
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

#[AsForm('tenants.roles.form', can: TenantPermission::ManageRoles, on: 'tenant')]
final class RoleForm extends FormDefinition
{
    use ActivatesTenantContext;

    public function __construct(private readonly PermissionRepository $permissions) {}

    public function definition(FormComponent $form, Request $request): FormComponent
    {
        /** @var Tenant $tenant */
        $tenant = $this->contextModel('tenant');
        /** @var Role|null $role */
        $role = $this->hasContext('role') ? $this->contextModel('role') : null;

        $uniqueName = Rule::unique('roles', 'name')->where('tenant_id', $tenant->id);

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
            $this->permissions->tenant(),
        );

        return $form
            ->method(HttpMethod::Post)
            ->schema([
                Grid::make('tenants-roles-fields')
                    ->columns(1)
                    ->schema([
                        TextInput::make('name', __('common.field.name'))
                            ->placeholder(__('tenant.settings.roles.name.placeholder'))
                            ->required()
                            ->rules(['string', 'max:255', $uniqueName]),
                        CheckboxGroup::make('permissions', __('tenant.settings.roles.permissions'))
                            ->options($permissionOptions)
                            ->bulkToggleable()
                            ->helperText(__('tenant.settings.roles.permissions-help')),
                    ]),
            ])
            ->submitLabel($role instanceof Role ? __('tenant.settings.roles.save') : __('tenant.settings.roles.create.label'));
    }

    public function handle(FormData $data): LatticeResponse
    {
        /** @var Tenant $tenant */
        $tenant = $this->contextModel('tenant');
        /** @var Role|null $role */
        $role = $this->hasContext('role') ? $this->contextModel('role') : null;

        abort_if($role?->isOwner() === true, 403);

        $permissions = array_values((array) ($data['permissions'] ?? []));

        $event = $role instanceof Role ? 'updated' : 'created';

        $role = DB::transaction(function () use ($tenant, $role, $data, $permissions): Role {
            if ($role instanceof Role) {
                $role->update(['name' => (string) $data['name']]);
            } else {
                $role = $tenant->roles()->create(['name' => (string) $data['name']]);
            }

            $role->syncPermissions($permissions);

            return $role;
        });

        Audit::record($event === 'created' ? TenantAuditEvent::RoleCreated : TenantAuditEvent::RoleUpdated, $role, $tenant, [
            'name' => $role->name,
        ]);

        return Effects::respond()->toast(__("tenant.settings.roles.{$event}"), Variant::Success)
            ->toRoute('tenant.settings.edit', ['current_tenant' => $tenant->slug, 'tabs' => 'roles']);
    }
}

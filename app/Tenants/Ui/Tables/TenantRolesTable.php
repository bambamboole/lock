<?php
declare(strict_types=1);

namespace App\Tenants\Ui\Tables;

use App\Tenants\Enums\TenantPermission;
use App\Tenants\Models\Role;
use App\Tenants\Models\Tenant;
use App\Tenants\Ui\Actions\DeleteRole;
use App\Tenants\Ui\Concerns\ActivatesTenantContext;
use App\Tenants\Ui\Forms\RoleForm;
use Lattice\Actions\Components\Action;
use Lattice\Actions\Components\ActionGroup;
use Lattice\Form\Components\Form;
use Lattice\Table\Attributes\AsTable;
use Lattice\Table\CallbackTableSource;
use Lattice\Table\Columns\NumberColumn;
use Lattice\Table\Columns\TextColumn;
use Lattice\Table\Contracts\TableSource;
use Lattice\Table\Enums\PaginationType;
use Lattice\Table\TableDefinition;
use Lattice\Table\TableQuery;
use Lattice\Table\TableResult;
use Lattice\Ui\Components\Button;
use Lattice\Ui\Components\Modal;

#[AsTable('tenants.roles', can: TenantPermission::ManageRoles, on: 'tenant')]
final class TenantRolesTable extends TableDefinition
{
    use ActivatesTenantContext;

    public function layout(): string
    {
        return 'grid';
    }

    public function pagination(): PaginationType
    {
        return PaginationType::None;
    }

    public function columns(): array
    {
        return [
            TextColumn::make('name')->label(__('tenant.settings.roles.column.name')),
            NumberColumn::make('members_count')->label(__('tenant.settings.roles.column.members')),
            NumberColumn::make('permissions_count')->label(__('tenant.settings.roles.column.permissions')),
        ];
    }

    public function toolbar(): array
    {
        return [
            Button::make(__('tenant.settings.roles.create.label'), 'create-role')
                ->modal(
                    Modal::make('tenants.roles.create-modal')
                        ->title(__('tenant.settings.roles.create.heading'))
                        ->schema([
                            Form::use(RoleForm::class),
                        ]),
                ),
        ];
    }

    public function actions(array $row): array
    {
        if ($row['is_owner'] ?? false) {
            return [];
        }

        return [
            ActionGroup::make("tenants.roles.{$row['id']}.actions")
                ->label(__('tenant.settings.roles.actions.label'))
                ->actions([
                    Button::make(__('tenant.settings.roles.edit.label'))
                        ->modal(
                            Modal::make("tenants.roles.{$row['id']}.edit-modal")
                                ->title(__('tenant.settings.roles.edit.heading'))
                                ->schema([
                                    Form::use(RoleForm::class, ['role' => $row['id']])
                                        ->fill([
                                            'name' => $row['edit_name'],
                                            'permissions' => $row['edit_permissions'],
                                        ]),
                                ]),
                        ),
                    Action::use(DeleteRole::class, ['role' => $row['id']]),
                ]),
        ];
    }

    public function source(): TableSource
    {
        return new CallbackTableSource(function (TableQuery $query): TableResult {
            /** @var Tenant $tenant */
            $tenant = $this->contextModel('tenant');

            $roles = $tenant->roles()
                ->with('permissions')
                ->withCount(['users', 'permissions'])
                ->orderByRaw("CASE preset WHEN 'owner' THEN 0 WHEN 'admin' THEN 1 WHEN 'member' THEN 2 ELSE 3 END")
                ->orderBy('name')
                ->get();

            return TableResult::fromItems(
                $roles->map(fn (Role $role): array => [
                    'id' => $role->id,
                    'name' => $role->displayName(),
                    'members_count' => $role->users_count,
                    'permissions_count' => $role->permissions_count,
                    'is_owner' => $role->isOwner(),
                    // The edit modal now hangs off the row action, so the row
                    // carries what its form prefills with: the stored name rather
                    // than the preset label the column shows.
                    'edit_name' => $role->name,
                    'edit_permissions' => $role->permissionValues(),
                ]),
            );
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Admin\Ui\Tables;

use App\Admin\Enums\AdminPermission;
use App\Auth\Models\User;
use App\Tenants\Enums\RolePreset;
use App\Tenants\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Lattice\Facades\Effects;
use Lattice\Table\Attributes\AsTable;
use Lattice\Table\Columns\BooleanColumn;
use Lattice\Table\Columns\NumberColumn;
use Lattice\Table\Columns\TextColumn;
use Lattice\Table\Sources\Eloquent\EloquentTableDefinition;
use Lattice\Table\TableQuery;
use Lattice\Ui\Components\Button;
use Lattice\Ui\Enums\Emphasis;

/**
 * @extends EloquentTableDefinition<Tenant>
 */
#[AsTable('admin.tenants', can: [AdminPermission::Access, AdminPermission::ManageTenants])]
final class AdminTenantsTable extends EloquentTableDefinition
{
    /**
     * @return Builder<Tenant>
     */
    public function builder(TableQuery $query): Builder
    {
        $builder = Tenant::query()
            ->withCount('members')
            ->addSelect([
                'owner_name' => User::query()
                    ->select('users.name')
                    ->join('user_role', 'user_role.user_id', '=', 'users.id')
                    ->join('roles', 'roles.id', '=', 'user_role.role_id')
                    ->whereColumn('roles.tenant_id', 'tenants.id')
                    ->where('roles.preset', RolePreset::Owner->value)
                    ->limit(1),
            ]);

        if ($query->sorts === []) {
            $builder->latest('id');
        }

        return $builder;
    }

    public function columns(): array
    {
        return [
            TextColumn::make('name')->label(__('admin.tenants.columns.name'))->searchable(),
            TextColumn::make('slug')->label(__('admin.tenants.columns.slug'))->searchable(),
            BooleanColumn::make('is_personal')->label(__('admin.tenants.columns.personal')),
            NumberColumn::make('members_count')->label(__('admin.tenants.columns.members')),
            TextColumn::make('owner_name')->label(__('admin.tenants.columns.owner')),
            TextColumn::make('created_at')->label(__('admin.tenants.columns.created-at'))->dateTime()->sortable(),
        ];
    }

    public function actions(array $row): array
    {
        return [
            Button::make(__('admin.tenants.actions.manage'))
                ->emphasis(Emphasis::Ghost)
                ->effects(Effects::redirect(route('admin.tenants.show', ['tenant' => $row['slug']], false))),
        ];
    }
}

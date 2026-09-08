<?php

declare(strict_types=1);

namespace App\Admin\Ui\Tables;

use App\Admin\Enums\AdminPermission;
use App\Auth\Models\User;
use App\Realms\Enums\RolePreset;
use App\Realms\Models\Realm;
use Illuminate\Database\Eloquent\Builder;
use Lattice\Facades\Effects;
use Lattice\Table\Attributes\AsTable;
use Lattice\Table\Columns\NumberColumn;
use Lattice\Table\Columns\TextColumn;
use Lattice\Table\Sources\Eloquent\EloquentTableDefinition;
use Lattice\Table\TableQuery;
use Lattice\Ui\Components\Button;
use Lattice\Ui\Enums\Emphasis;

/**
 * @extends EloquentTableDefinition<Realm>
 */
#[AsTable('admin.realms', can: [AdminPermission::Access, AdminPermission::ManageRealms])]
final class AdminRealmsTable extends EloquentTableDefinition
{
    /**
     * @return Builder<Realm>
     */
    public function builder(TableQuery $query): Builder
    {
        $builder = Realm::query()
            ->withCount('users')
            ->addSelect([
                'owner_name' => User::query()
                    ->select('users.name')
                    ->join('user_role', 'user_role.user_id', '=', 'users.id')
                    ->join('roles', 'roles.id', '=', 'user_role.role_id')
                    ->whereColumn('roles.realm_id', 'realms.id')
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
            TextColumn::make('name')->label(__('admin.realms.columns.name'))->searchable(),
            TextColumn::make('slug')->label(__('admin.realms.columns.slug'))->searchable(),
            NumberColumn::make('users_count')->label(__('admin.realms.columns.users')),
            TextColumn::make('owner_name')->label(__('admin.realms.columns.owner')),
            TextColumn::make('created_at')->label(__('admin.realms.columns.created-at'))->dateTime()->sortable(),
        ];
    }

    public function actions(array $row): array
    {
        return [
            Button::make(__('admin.realms.actions.manage'))
                ->emphasis(Emphasis::Ghost)
                ->effects(Effects::redirect(route('admin.realms.show', ['realm' => $row['slug']], false))),
        ];
    }
}

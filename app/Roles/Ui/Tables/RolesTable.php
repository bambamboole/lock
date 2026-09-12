<?php
declare(strict_types=1);

namespace App\Roles\Ui\Tables;

use App\Realms\Models\Realm;
use App\Roles\Models\Role;
use App\Roles\Ui\Actions\DeleteRoleAction;
use App\Roles\Ui\Actions\UpdateRoleAction;
use App\Shared\Api\Enums\ApiScope;
use Illuminate\Database\Eloquent\Builder;
use Lattice\Actions\Components\Action;
use Lattice\Table\Attributes\AsTable;
use Lattice\Table\Columns\NumberColumn;
use Lattice\Table\Columns\TextColumn;
use Lattice\Table\Sources\Eloquent\EloquentTableDefinition;
use Lattice\Table\TableQuery;

/**
 * @extends EloquentTableDefinition<Role>
 */
#[AsTable(self::ID, can: ApiScope::RolesRead)]
final class RolesTable extends EloquentTableDefinition
{
    public const string ID = 'admin.realm-roles';

    /**
     * @return Builder<Role>
     */
    public function builder(TableQuery $query): Builder
    {
        $realm = $this->contextModel('realm', Realm::class);

        $builder = $realm->roles()->getQuery()
            ->select('roles.*')
            ->withCount('users');

        if ($query->sorts === []) {
            $builder->orderBy('name');
        }

        return $builder;
    }

    public function columns(): array
    {
        return [
            TextColumn::make('name')->label(__('admin.roles.columns.name'))->searchable()->sortable(),
            TextColumn::make('description')->label(__('admin.roles.columns.description'))->searchable(),
            NumberColumn::make('users_count')->label(__('admin.roles.columns.users'))->decimals(0),
        ];
    }

    public function actions(array $row): array
    {
        $context = ['role' => $row['id']];

        return [
            Action::use(UpdateRoleAction::class, $context),
            Action::use(DeleteRoleAction::class, $context),
        ];
    }
}

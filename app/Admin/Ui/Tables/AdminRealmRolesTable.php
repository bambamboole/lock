<?php
declare(strict_types=1);

namespace App\Admin\Ui\Tables;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Actions\DeleteRealmRoleAction;
use App\Admin\Ui\Actions\UpdateRealmRoleAction;
use App\Realms\Models\Realm;
use App\Realms\Models\RealmRole;
use Illuminate\Database\Eloquent\Builder;
use Lattice\Actions\Components\Action;
use Lattice\Table\Attributes\AsTable;
use Lattice\Table\Columns\NumberColumn;
use Lattice\Table\Columns\TextColumn;
use Lattice\Table\Sources\Eloquent\EloquentTableDefinition;
use Lattice\Table\TableQuery;

/**
 * @extends EloquentTableDefinition<RealmRole>
 */
#[AsTable(self::ID, can: [AdminPermission::Access, AdminPermission::ManageUsers])]
final class AdminRealmRolesTable extends EloquentTableDefinition
{
    public const string ID = 'admin.realm-roles';

    /**
     * @return Builder<RealmRole>
     */
    public function builder(TableQuery $query): Builder
    {
        $realm = $this->contextModel('realm', Realm::class);

        $builder = $realm->realmRoles()->getQuery()
            ->select('realm_roles.*')
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
        $context = ['realmRole' => $row['id']];

        return [
            Action::use(UpdateRealmRoleAction::class, $context),
            Action::use(DeleteRealmRoleAction::class, $context),
        ];
    }
}

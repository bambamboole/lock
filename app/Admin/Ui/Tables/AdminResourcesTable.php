<?php
declare(strict_types=1);

namespace App\Admin\Ui\Tables;

use App\Admin\Ui\Actions\DeleteResourceAction;
use App\Admin\Ui\Actions\UpdateResourceAction;
use App\Realms\Models\Realm;
use App\Shared\Admin\Enums\AdminPermission;
use App\Shared\Ui\Components\ActionBar;
use Illuminate\Database\Eloquent\Builder;
use Lattice\Actions\Components\Action;
use Lattice\Table\Attributes\AsTable;
use Lattice\Table\Columns\NumberColumn;
use Lattice\Table\Columns\TextColumn;
use Lattice\Table\Components\RowClick;
use Lattice\Table\Sources\Eloquent\EloquentTableDefinition;
use Lattice\Table\TableQuery;

/**
 * @extends EloquentTableDefinition<\App\Resources\Models\Resource>
 */
#[AsTable(self::ID, can: [AdminPermission::Access, AdminPermission::ViewResources])]
final class AdminResourcesTable extends EloquentTableDefinition
{
    public const string ID = 'admin.resources';

    /**
     * @return Builder<\App\Resources\Models\Resource>
     */
    public function builder(TableQuery $query): Builder
    {
        $realm = $this->contextModel('realm', Realm::class);

        $builder = $realm->realmResources()->getQuery()
            ->select('resources.*')
            ->withCount('scopes');

        if ($query->sorts === []) {
            $builder->orderBy('identifier');
        }

        return $builder;
    }

    public function columns(): array
    {
        return [
            TextColumn::make('name')->label(__('admin.resources.columns.name'))->searchable()->sortable(),
            TextColumn::make('identifier')->label(__('admin.resources.columns.identifier'))->searchable()->sortable()->copyable(),
            NumberColumn::make('scopes_count')->label(__('admin.resources.columns.scopes'))->decimals(0),
        ];
    }

    #[\Override]
    public function rowClick(array $row): RowClick
    {
        return RowClick::make()->href(route('admin.realms.resources.show', ['realm' => $this->contextString('realm'), 'resource' => $row['id']], false));
    }

    public function actions(array $row): array
    {
        $context = ['resource' => $row['id']];

        return array_filter([ActionBar::menu('admin.resources.row-actions', [
            Action::use(UpdateResourceAction::class, $context),
            Action::use(DeleteResourceAction::class, $context),
        ])]);
    }
}

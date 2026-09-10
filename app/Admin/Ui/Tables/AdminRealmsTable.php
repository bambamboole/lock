<?php

declare(strict_types=1);

namespace App\Admin\Ui\Tables;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Concerns\OpensRealmDeletion;
use App\Realms\Models\Realm;
use App\Shared\Ui\Components\ActionBar;
use Illuminate\Database\Eloquent\Builder;
use Lattice\Table\Attributes\AsTable;
use Lattice\Table\Columns\NumberColumn;
use Lattice\Table\Columns\TextColumn;
use Lattice\Table\Components\RowClick;
use Lattice\Table\Sources\Eloquent\EloquentTableDefinition;
use Lattice\Table\TableQuery;

/**
 * @extends EloquentTableDefinition<Realm>
 */
#[AsTable('admin.realms', can: [AdminPermission::Access, AdminPermission::ViewRealms])]
final class AdminRealmsTable extends EloquentTableDefinition
{
    use OpensRealmDeletion;

    /**
     * @return Builder<Realm>
     */
    public function builder(TableQuery $query): Builder
    {
        $builder = Realm::query()->withCount('users');

        if ($query->sorts === []) {
            $builder->orderBy('name');
        }

        return $builder;
    }

    public function columns(): array
    {
        return [
            TextColumn::make('name')->label(__('admin.realms.columns.name'))->searchable()->sortable(),
            TextColumn::make('slug')->label(__('admin.realms.columns.slug'))->searchable(),
            NumberColumn::make('users_count')->label(__('admin.realms.columns.users')),
            TextColumn::make('created_at')->label(__('admin.realms.columns.created-at'))->dateTime()->sortable(),
        ];
    }

    #[\Override]
    public function rowClick(array $row): RowClick
    {
        return RowClick::make()->href(route('admin.realms.settings', ['realm' => $row['slug']], false));
    }

    /**
     * The realm's settings page offers deletion and nothing else, so that is
     * what the row carries — the master realm cannot be deleted and gets no
     * menu at all.
     */
    public function actions(array $row): array
    {
        return array_filter([ActionBar::menu('admin.realms.row-actions', [$this->deleteRealmTrigger((string) $row['slug'])])]);
    }
}

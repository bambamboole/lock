<?php

declare(strict_types=1);

namespace App\Admin\Ui\Tables;

use App\Admin\Enums\AdminPermission;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use Illuminate\Database\Eloquent\Builder;
use Lattice\Core\Enums\ColorName;
use Lattice\Facades\Effects;
use Lattice\Table\Attributes\AsTable;
use Lattice\Table\Columns\BooleanColumn;
use Lattice\Table\Columns\StackColumn;
use Lattice\Table\Columns\TextColumn;
use Lattice\Table\Sources\Eloquent\EloquentTableDefinition;
use Lattice\Table\TableQuery;
use Lattice\Ui\Components\Button;
use Lattice\Ui\Components\Text;
use Lattice\Ui\Enums\Emphasis;
use Lattice\Ui\Enums\Size;

/**
 * @extends EloquentTableDefinition<User>
 */
#[AsTable('admin.users', can: [AdminPermission::Access, AdminPermission::ManageUsers])]
final class AdminUsersTable extends EloquentTableDefinition
{
    /**
     * @return Builder<User>
     */
    public function builder(TableQuery $query): Builder
    {
        $realm = $this->contextModel('realm');

        abort_unless($realm instanceof Realm, 404);

        $builder = $realm->users()->getQuery()->withExists('roles');

        if ($query->sorts === []) {
            $builder->latest('id');
        }

        return $builder;
    }

    public function columns(): array
    {
        return [
            TextColumn::make('name')->searchable()->visible(false),
            TextColumn::make('email')->searchable()->visible(false),
            StackColumn::make('member')
                ->label(__('admin.users.columns.member'))
                ->schema([
                    Text::bound('name'),
                    Text::bound('email')->color(ColorName::Muted)->size(Size::Sm),
                ]),
            BooleanColumn::make('roles_exists')->label(__('admin.users.columns.admin')),
            TextColumn::make('created_at')->label(__('admin.users.columns.created-at'))->dateTime()->sortable(),
        ];
    }

    public function actions(array $row): array
    {
        return [
            Button::make(__('admin.users.actions.manage'))
                ->emphasis(Emphasis::Ghost)
                ->effects(Effects::redirect(route('admin.realms.users.show', ['realm' => $this->contextString('realm'), 'user' => $row['id']], false))),
        ];
    }
}

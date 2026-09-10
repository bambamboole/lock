<?php

declare(strict_types=1);

namespace App\Admin\Ui\Tables;

use App\Admin\Enums\AdminPermission;
use App\Auth\Models\User;
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
        $builder = User::query()->with('realm')->withExists('roles');

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
            TextColumn::make('realm.name')->label(__('admin.users.columns.realm')),
            BooleanColumn::make('roles_exists')->label(__('admin.users.columns.admin')),
            TextColumn::make('created_at')->label(__('admin.users.columns.created-at'))->dateTime()->sortable(),
        ];
    }

    public function actions(array $row): array
    {
        return [
            Button::make(__('admin.users.actions.manage'))
                ->emphasis(Emphasis::Ghost)
                ->effects(Effects::redirect(route('admin.users.show', ['user' => $row['id']], false))),
        ];
    }
}

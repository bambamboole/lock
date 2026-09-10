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
use Lattice\Table\Filters\TernaryFilter;
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

        $builder = $realm->users()->getQuery()
            ->select('users.*')
            ->selectRaw('users.email_verified_at is not null as email_verified')
            ->selectRaw('users.blocked_at is not null as blocked')
            ->selectRaw(
                '(exists (select 1 from oidc_totp_factors where oidc_totp_factors.authenticatable_type = ? and oidc_totp_factors.authenticatable_id = users.id and oidc_totp_factors.confirmed_at is not null)'
                .' or exists (select 1 from passkeys where passkeys.user_id = users.id)) as mfa',
                [new User()->getMorphClass()],
            )
            ->withExists('roles')
            ->withCasts(['email_verified' => 'boolean', 'blocked' => 'boolean', 'mfa' => 'boolean']);

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
            BooleanColumn::make('email_verified')->label(__('admin.users.columns.verified')),
            BooleanColumn::make('mfa')->label(__('admin.users.columns.mfa')),
            BooleanColumn::make('blocked')->label(__('admin.users.columns.blocked')),
            BooleanColumn::make('roles_exists')->label(__('admin.users.columns.admin')),
            TextColumn::make('created_at')->label(__('admin.users.columns.created-at'))->dateTime()->sortable(),
        ];
    }

    public function filters(): array
    {
        return [
            TernaryFilter::make('verified')
                ->label(__('admin.users.filters.verified'))
                ->queries(
                    fn (Builder $users) => $users->whereNotNull('users.email_verified_at'),
                    fn (Builder $users) => $users->whereNull('users.email_verified_at'),
                ),
            TernaryFilter::make('blocked')
                ->label(__('admin.users.filters.blocked'))
                ->queries(
                    fn (Builder $users) => $users->whereNotNull('users.blocked_at'),
                    fn (Builder $users) => $users->whereNull('users.blocked_at'),
                ),
            TernaryFilter::make('mfa')
                ->label(__('admin.users.filters.mfa'))
                ->queries(
                    fn (Builder $users) => $users->where(fn (Builder $mfa) => $mfa
                        ->whereHas('totpFactors', fn (Builder $factors) => $factors->whereNotNull('confirmed_at'))
                        ->orWhereHas('passkeys')),
                    fn (Builder $users) => $users
                        ->whereDoesntHave('totpFactors', fn (Builder $factors) => $factors->whereNotNull('confirmed_at'))
                        ->whereDoesntHave('passkeys'),
                ),
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

<?php
declare(strict_types=1);

namespace App\Realms\Ui\Tables;

use App\Auth\Models\User;
use App\Realms\Enums\RealmPermission;
use App\Realms\Models\Realm;
use App\Realms\Support\RealmMemberRoles;
use App\Realms\Ui\Actions\RemoveMember;
use App\Realms\Ui\Actions\UpdateMemberRole;
use App\Realms\Ui\Concerns\ActivatesRealmContext;
use Lattice\Actions\Components\Action;
use Lattice\Actions\Components\ActionGroup;
use Lattice\Core\Enums\ColorName;
use Lattice\Table\Attributes\AsTable;
use Lattice\Table\CallbackTableSource;
use Lattice\Table\Columns\StackColumn;
use Lattice\Table\Columns\TextColumn;
use Lattice\Table\Contracts\TableSource;
use Lattice\Table\Enums\PaginationType;
use Lattice\Table\TableDefinition;
use Lattice\Table\TableQuery;
use Lattice\Table\TableResult;
use Lattice\Ui\Components\Text;
use Lattice\Ui\Enums\Size;

#[AsTable('realms.members', can: 'view', on: 'realm')]
final class RealmMembersTable extends TableDefinition
{
    use ActivatesRealmContext;

    public function __construct(private readonly RealmMemberRoles $memberRoles) {}

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
            StackColumn::make('member')
                ->label(__('realm.members.column'))
                ->schema([
                    Text::bound('name'),
                    Text::bound('email')->color(ColorName::Muted)->size(Size::Sm),
                ]),
            TextColumn::make('role_labels')->label(__('common.field.role')),
        ];
    }

    public function actions(array $row): array
    {
        /** @var Realm $realm */
        $realm = $this->contextModel('realm');
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        if ($row['is_owner'] ?? false) {
            return [];
        }

        $context = ['member' => $row['id']];
        $actions = [];

        if ($user->can(RealmPermission::ManageMembers, $realm)) {
            $actions[] = Action::use(UpdateMemberRole::class, $context);
        }

        if ($user->can(RealmPermission::ManageMembers, $realm)) {
            $actions[] = Action::use(RemoveMember::class, $context);
        }

        if ($actions === []) {
            return [];
        }

        return [
            ActionGroup::make("realms.members.{$row['id']}.actions")
                ->label(__('realm.members.actions.label'))
                ->actions($actions),
        ];
    }

    public function source(): TableSource
    {
        return new CallbackTableSource(function (TableQuery $query): TableResult {
            /** @var Realm $realm */
            $realm = $this->contextModel('realm');

            return TableResult::fromItems($this->memberRoles->rows($realm, $query->search));
        });
    }
}

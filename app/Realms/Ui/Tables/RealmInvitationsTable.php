<?php
declare(strict_types=1);

namespace App\Realms\Ui\Tables;

use App\Auth\Models\User;
use App\Realms\Enums\RealmPermission;
use App\Realms\Models\Realm;
use App\Realms\Models\RealmInvitation;
use App\Realms\Ui\Actions\CancelInvitation;
use App\Realms\Ui\Actions\ResendInvitation;
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

#[AsTable('realms.invitations', can: 'view', on: 'realm')]
final class RealmInvitationsTable extends TableDefinition
{
    use ActivatesRealmContext;

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
            StackColumn::make('invitation')
                ->label(__('realm.invitations.column'))
                ->schema([
                    Text::bound('email'),
                    Text::bound('role_label')->color(ColorName::Muted)->size(Size::Sm),
                ]),
            TextColumn::make('created_at')->label(__('realm.invitations.sent-column')),
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

        $context = ['invitation' => $row['code']];

        $actions = [
            ...$user->can(RealmPermission::ManageMembers, $realm)
                ? [Action::use(ResendInvitation::class, $context)]
                : [],
            ...$user->can(RealmPermission::ManageMembers, $realm)
                ? [Action::use(CancelInvitation::class, $context)]
                : [],
        ];

        if ($actions === []) {
            return [];
        }

        return [
            ActionGroup::make("realms.invitations.{$row['id']}.actions")
                ->label(__('realm.invitations.actions.label'))
                ->actions($actions),
        ];
    }

    public function source(): TableSource
    {
        return new CallbackTableSource(function (TableQuery $query): TableResult {
            /** @var Realm $realm */
            $realm = $this->contextModel('realm');

            $invitations = $realm->invitations()
                ->with('role')
                ->whereNull('accepted_at')
                ->oldest();

            if ($query->search !== '') {
                $invitations->whereLike('email', "%{$query->search}%");
            }

            return TableResult::fromItems(
                $invitations->get()
                    ->map(fn (RealmInvitation $invitation): array => [
                        'id' => $invitation->id,
                        'code' => $invitation->code,
                        'email' => $invitation->email,
                        'role_label' => $invitation->role?->displayName() ?? '',
                        'created_at' => $invitation->created_at?->diffForHumans() ?? '',
                    ]),
            );
        });
    }
}

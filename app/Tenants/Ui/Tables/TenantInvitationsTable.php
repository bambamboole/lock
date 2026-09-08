<?php
declare(strict_types=1);

namespace App\Tenants\Ui\Tables;

use App\Auth\Models\User;
use App\Tenants\Enums\TenantPermission;
use App\Tenants\Models\Tenant;
use App\Tenants\Models\TenantInvitation;
use App\Tenants\Ui\Actions\CancelInvitation;
use App\Tenants\Ui\Actions\ResendInvitation;
use App\Tenants\Ui\Concerns\ActivatesTenantContext;
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

#[AsTable('tenants.invitations', can: 'view', on: 'tenant')]
final class TenantInvitationsTable extends TableDefinition
{
    use ActivatesTenantContext;

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
                ->label(__('tenant.invitations.column'))
                ->schema([
                    Text::bound('email'),
                    Text::bound('role_label')->color(ColorName::Muted)->size(Size::Sm),
                ]),
            TextColumn::make('created_at')->label(__('tenant.invitations.sent-column')),
        ];
    }

    public function actions(array $row): array
    {
        /** @var Tenant $tenant */
        $tenant = $this->contextModel('tenant');
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $context = ['invitation' => $row['code']];

        $actions = [
            ...$user->can(TenantPermission::ManageMembers, $tenant)
                ? [Action::use(ResendInvitation::class, $context)]
                : [],
            ...$user->can(TenantPermission::ManageMembers, $tenant)
                ? [Action::use(CancelInvitation::class, $context)]
                : [],
        ];

        if ($actions === []) {
            return [];
        }

        return [
            ActionGroup::make("tenants.invitations.{$row['id']}.actions")
                ->label(__('tenant.invitations.actions.label'))
                ->actions($actions),
        ];
    }

    public function source(): TableSource
    {
        return new CallbackTableSource(function (TableQuery $query): TableResult {
            /** @var Tenant $tenant */
            $tenant = $this->contextModel('tenant');

            $invitations = $tenant->invitations()
                ->with('role')
                ->whereNull('accepted_at')
                ->oldest();

            if ($query->search !== '') {
                $invitations->whereLike('email', "%{$query->search}%");
            }

            return TableResult::fromItems(
                $invitations->get()
                    ->map(fn (TenantInvitation $invitation): array => [
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

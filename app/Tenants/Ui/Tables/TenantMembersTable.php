<?php
declare(strict_types=1);

namespace App\Tenants\Ui\Tables;

use App\Auth\Models\User;
use App\Tenants\Enums\TenantPermission;
use App\Tenants\Models\Tenant;
use App\Tenants\Support\TenantMemberRoles;
use App\Tenants\Ui\Actions\RemoveMember;
use App\Tenants\Ui\Actions\UpdateMemberRole;
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

#[AsTable('tenants.members', can: 'view', on: 'tenant')]
final class TenantMembersTable extends TableDefinition
{
    use ActivatesTenantContext;

    public function __construct(private readonly TenantMemberRoles $memberRoles) {}

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
                ->label(__('tenant.members.column'))
                ->schema([
                    Text::bound('name'),
                    Text::bound('email')->color(ColorName::Muted)->size(Size::Sm),
                ]),
            TextColumn::make('role_labels')->label(__('common.field.role')),
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

        if ($row['is_owner'] ?? false) {
            return [];
        }

        $context = ['member' => $row['id']];
        $actions = [];

        if ($user->can(TenantPermission::ManageMembers, $tenant)) {
            $actions[] = Action::use(UpdateMemberRole::class, $context);
        }

        if ($user->can(TenantPermission::ManageMembers, $tenant)) {
            $actions[] = Action::use(RemoveMember::class, $context);
        }

        if ($actions === []) {
            return [];
        }

        return [
            ActionGroup::make("tenants.members.{$row['id']}.actions")
                ->label(__('tenant.members.actions.label'))
                ->actions($actions),
        ];
    }

    public function source(): TableSource
    {
        return new CallbackTableSource(function (TableQuery $query): TableResult {
            /** @var Tenant $tenant */
            $tenant = $this->contextModel('tenant');

            return TableResult::fromItems($this->memberRoles->rows($tenant, $query->search));
        });
    }
}

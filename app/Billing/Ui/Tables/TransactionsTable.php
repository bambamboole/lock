<?php

declare(strict_types=1);

namespace App\Billing\Ui\Tables;

use App\Tenants\Enums\TenantPermission;
use App\Tenants\Models\Tenant;
use App\Tenants\Ui\Concerns\ActivatesTenantContext;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Paddle\Transaction;
use Lattice\Table\Attributes\AsTable;
use Lattice\Table\Columns\TextColumn;
use Lattice\Table\Enums\PaginationType;
use Lattice\Table\Sources\Eloquent\EloquentTableDefinition;
use Lattice\Table\TableQuery;

/**
 * @extends EloquentTableDefinition<Transaction>
 */
#[AsTable('billing.transactions', can: TenantPermission::ManageBilling, on: 'tenant')]
final class TransactionsTable extends EloquentTableDefinition
{
    use ActivatesTenantContext;

    public function pagination(): PaginationType
    {
        return PaginationType::Simple;
    }

    /**
     * @return Builder<Transaction>
     */
    public function builder(TableQuery $query): Builder
    {
        /** @var Tenant $tenant */
        $tenant = $this->contextModel('tenant');

        return Transaction::query()
            ->where('billable_type', Tenant::class)
            ->where('billable_id', $tenant->id)
            ->latest('billed_at');
    }

    /**
     * @return array<int, TextColumn>
     */
    public function columns(): array
    {
        return [
            TextColumn::make('paddle_id')->label(__('billing.transactions.columns.reference')),
            TextColumn::make('billed_at')->label(__('billing.transactions.columns.date'))->dateTime(),
            TextColumn::make('status')->label(__('billing.transactions.columns.status')),
            TextColumn::make('total')->label(__('billing.transactions.columns.total')),
        ];
    }
}

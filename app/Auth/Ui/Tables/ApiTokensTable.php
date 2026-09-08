<?php
declare(strict_types=1);

namespace App\Auth\Ui\Tables;

use App\Auth\Models\OAuthAccessToken;
use App\Auth\Ui\Actions\RevokeApiToken;
use App\Auth\Ui\Fragments\ApiTokenScopesFragment;
use App\Shared\Concerns\ResolvesCurrentUser;
use App\Tenants\Models\Tenant;
use App\Tenants\Ui\Concerns\ActivatesTenantContext;
use Illuminate\Database\Eloquent\Builder;
use Lattice\Actions\Components\Action;
use Lattice\Actions\Components\ActionGroup;
use Lattice\Fragments\Components\Fragment;
use Lattice\Table\Attributes\AsTable;
use Lattice\Table\CallbackTableSource;
use Lattice\Table\Columns\TextColumn;
use Lattice\Table\Contracts\TableSource;
use Lattice\Table\Enums\PaginationType;
use Lattice\Table\TableDefinition;
use Lattice\Table\TableQuery;
use Lattice\Table\TableResult;

#[AsTable('tenant.settings.api-tokens', can: 'view', on: 'tenant')]
final class ApiTokensTable extends TableDefinition
{
    use ActivatesTenantContext;
    use ResolvesCurrentUser;

    public function pagination(): PaginationType
    {
        return PaginationType::None;
    }

    public function columns(): array
    {
        return [
            TextColumn::make('name')->label(__('tenant.settings.api-tokens.columns.name'))->searchable(),
            TextColumn::make('scopes')->label(__('tenant.settings.api-tokens.columns.scopes'))->searchable(),
            TextColumn::make('created_at')->label(__('tenant.settings.api-tokens.columns.created'))->dateTime(),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function rowDetail(array $row): ?Fragment
    {
        if (($row['scopes'] ?? '') === '') {
            return null;
        }

        return Fragment::lazy(ApiTokenScopesFragment::class, ['token' => $row['id']]);
    }

    public function actions(array $row): array
    {
        return [
            ActionGroup::make("tenants.api-tokens.{$row['id']}.actions")
                ->label(__('tenant.settings.api-tokens.actions.label'))
                ->actions([
                    Action::use(RevokeApiToken::class, ['token_id' => $row['id']]),
                ]),
        ];
    }

    public function source(): TableSource
    {
        return new CallbackTableSource(function (TableQuery $query): TableResult {
            /** @var Tenant $tenant */
            $tenant = $this->contextModel('tenant');

            $tokens = OAuthAccessToken::query()
                ->where('user_id', $this->currentUser()->id)
                ->where('tenant_id', $tenant->id)
                ->where('revoked', false)->latest();

            if ($query->search !== '') {
                $tokens->where(function (Builder $builder) use ($query): void {
                    $builder->whereLike('name', "%{$query->search}%")
                        ->orWhereLike('scopes', "%{$query->search}%");
                });
            }

            $rows = $tokens->get()
                ->map(fn (OAuthAccessToken $token): array => [
                    'id' => $token->id,
                    'name' => $token->name,
                    'scopes' => implode(', ', (array) $token->scopes),
                    'created_at' => $token->created_at,
                ]);

            return TableResult::fromItems($rows);
        });
    }
}

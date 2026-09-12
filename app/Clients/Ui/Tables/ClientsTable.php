<?php
declare(strict_types=1);

namespace App\Clients\Ui\Tables;

use App\Clients\Ui\Actions\DeleteClient;
use App\Clients\Ui\Actions\RestoreClient;
use App\Clients\Ui\Actions\RevokeClient;
use App\Clients\Ui\Actions\RotateClientSecret;
use App\Realms\Models\Realm;
use App\Shared\Api\Enums\ApiScope;
use App\Shared\Clients\Data\ClientAttributes;
use App\Shared\Ui\Components\ActionBar;
use Bambamboole\LaravelOidc\Server\Clients\Enums\TokenEndpointAuthMethod;
use Bambamboole\LaravelOidc\Server\Clients\Models\Client;
use Illuminate\Database\Eloquent\Builder;
use Lattice\Actions\Components\Action;
use Lattice\Table\Attributes\AsTable;
use Lattice\Table\Columns\BadgeColumn;
use Lattice\Table\Columns\TextColumn;
use Lattice\Table\Components\RowClick;
use Lattice\Table\Sources\Eloquent\EloquentTableDefinition;
use Lattice\Table\TableQuery;

/**
 * @extends EloquentTableDefinition<Client>
 */
#[AsTable('admin.clients', can: ApiScope::ClientsRead)]
final class ClientsTable extends EloquentTableDefinition
{
    /**
     * @return Builder<Client>
     */
    public function builder(TableQuery $query): Builder
    {
        $realm = $this->contextModel('realm', Realm::class);

        $builder = Client::query()
            ->inRealm($realm->slug)
            ->select('oidc_clients.*')
            ->selectRaw("CASE WHEN revoked THEN 'blocked' ELSE 'active' END AS status");

        if ($query->sorts === []) {
            $builder->orderBy('name');
        }

        return $builder;
    }

    public function columns(): array
    {
        return [
            TextColumn::make('name')->label(__('admin.clients.columns.name'))->searchable()->sortable(),
            TextColumn::make('client_id')->label(__('admin.clients.columns.client-id'))->searchable()->copyable(),
            BadgeColumn::make('token_endpoint_auth_method')
                ->label(__('admin.clients.columns.type'))
                ->options([
                    TokenEndpointAuthMethod::None->value => __('admin.clients.type-badges.public'),
                    TokenEndpointAuthMethod::ClientSecretBasic->value => __('admin.clients.type-badges.confidential'),
                    TokenEndpointAuthMethod::ClientSecretPost->value => __('admin.clients.type-badges.confidential'),
                ])
                ->colors([
                    TokenEndpointAuthMethod::None->value => 'blue',
                    TokenEndpointAuthMethod::ClientSecretBasic->value => 'purple',
                    TokenEndpointAuthMethod::ClientSecretPost->value => 'purple',
                ]),
            TextColumn::make('grant_types')->label(__('admin.clients.columns.grant-types'))->options($this->grantTypeLabels()),
            BadgeColumn::make('status')
                ->label(__('admin.clients.columns.status'))
                ->options([
                    'active' => __('admin.clients.status.active'),
                    'blocked' => __('admin.clients.status.blocked'),
                ])
                ->colors(['active' => 'green', 'blocked' => 'red']),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function grantTypeLabels(): array
    {
        $labels = [];

        foreach ([...ClientAttributes::GRANT_TYPES, 'personal_access'] as $grantType) {
            $labels[$grantType] = __('admin.clients.grant-types.'.str_replace('_', '-', $grantType));
        }

        return $labels;
    }

    #[\Override]
    public function rowClick(array $row): RowClick
    {
        return RowClick::make()->href(route('admin.realms.clients.show', ['realm' => $this->contextString('realm'), 'client' => $row['id']], false));
    }

    public function actions(array $row): array
    {
        $context = ['client' => $row['id']];
        $confidential = $row['token_endpoint_auth_method'] !== TokenEndpointAuthMethod::None->value;

        return array_filter([ActionBar::menu('admin.clients.row-actions', [
            ...($confidential ? [Action::use(RotateClientSecret::class, $context)] : []),
            ...((bool) ($row['revoked'] ?? false)
                ? [Action::use(RestoreClient::class, $context)]
                : [Action::use(RevokeClient::class, $context)]),
            Action::use(DeleteClient::class, $context),
        ])]);
    }
}

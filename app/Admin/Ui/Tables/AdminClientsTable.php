<?php
declare(strict_types=1);

namespace App\Admin\Ui\Tables;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Actions\DeleteClient;
use App\Admin\Ui\Actions\RestoreClient;
use App\Admin\Ui\Actions\RevokeClient;
use App\Admin\Ui\Actions\RotateClientSecret;
use App\Realms\Models\Realm;
use App\Shared\Realms\Data\ClientAttributes;
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
#[AsTable('admin.clients', can: [AdminPermission::Access, AdminPermission::ManageRealms])]
final class AdminClientsTable extends EloquentTableDefinition
{
    /**
     * @return Builder<Client>
     */
    public function builder(TableQuery $query): Builder
    {
        $realm = $this->contextModel('realm');

        abort_unless($realm instanceof Realm, 404);

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
            TextColumn::make('grant_types')->label(__('admin.clients.columns.grant-types'))->options($this->grantTypeCombinations()),
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
     * The cell receives the JSON list as-is and matches it against the option
     * values by its comma-joined string form, so every combination of grant
     * types the console can assign gets a translated label.
     *
     * @return array<string, string>
     */
    private function grantTypeCombinations(): array
    {
        $labels = [];

        foreach (ClientAttributes::GRANT_TYPES as $grantType) {
            $labels[$grantType] = __('admin.clients.grant-types.'.str_replace('_', '-', $grantType));
        }

        $labels['personal_access'] = __('admin.clients.grant-types.personal-access');
        $options = [];
        $grantTypes = array_keys($labels);
        $count = count($grantTypes);

        for ($mask = 1; $mask < (1 << $count); $mask++) {
            $combination = array_values(array_filter($grantTypes, fn (int $index): bool => ($mask & (1 << $index)) !== 0, ARRAY_FILTER_USE_KEY));
            $options[implode(',', $combination)] = implode(', ', array_map(fn (string $grantType): string => $labels[$grantType], $combination));
        }

        return $options;
    }

    #[\Override]
    public function rowClick(array $row): RowClick
    {
        return RowClick::make()->href(route('admin.realms.clients.show', ['realm' => $this->contextString('realm'), 'client' => $row['id']], false));
    }

    /**
     * The same actions the client's detail page offers; whether a client is
     * blocked or holds a secret is read off the row, not re-queried.
     */
    public function actions(array $row): array
    {
        $context = ['realm' => $this->contextString('realm'), 'client' => $row['id']];
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

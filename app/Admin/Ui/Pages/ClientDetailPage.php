<?php
declare(strict_types=1);

namespace App\Admin\Ui\Pages;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Actions\DeleteClient;
use App\Admin\Ui\Actions\RestoreClient;
use App\Admin\Ui\Actions\RevokeClient;
use App\Admin\Ui\Actions\RotateClientSecret;
use App\Admin\Ui\Forms\UpdateClientForm;
use App\Realms\Models\Realm;
use App\Shared\Ui\Components\PageHeader;
use Bambamboole\LaravelOidc\Server\Clients\Models\Client;
use Illuminate\Http\Request;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsPage;
use Lattice\Core\Breadcrumb;
use Lattice\Core\Enums\ColorName;
use Lattice\Form\Components\Form;
use Lattice\Ui\Components\Badge;
use Lattice\Ui\Components\Card;
use Lattice\Ui\Components\CodeBlock;
use Lattice\Ui\Components\DescriptionList;
use Lattice\Ui\Components\Entries\ComponentEntry;
use Lattice\Ui\Components\Entries\DateEntry;
use Lattice\Ui\Components\Entries\TextEntry;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Components\Text;
use Lattice\Ui\Enums\DateTimeStyle;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\Enums\Orientation;
use Lattice\Ui\Enums\Size;
use Lattice\Ui\Enums\Width;
use Lattice\Ui\PageSchema;

#[AsPage(route: '/admin/realms/{realm}/clients/{client}', name: 'admin.realms.clients.show', can: [AdminPermission::Access, AdminPermission::ManageRealms])]
final class ClientDetailPage extends AdminPage
{
    private const string SECRET_FLASH_KEY = 'admin.clients.revealed-secret';

    private ?Realm $realm = null;

    private ?Client $client = null;

    /**
     * A client secret is readable exactly once: on the page that follows the
     * request that minted it. The column only ever holds the hash.
     */
    public static function revealSecret(?string $secret): void
    {
        if ($secret !== null) {
            session()->flash(self::SECRET_FLASH_KEY, $secret);
        }
    }

    public function title(): ?string
    {
        return $this->client?->name;
    }

    /**
     * @return array<int, Breadcrumb>
     */
    public function breadcrumbs(): array
    {
        if (! $this->realm instanceof Realm || ! $this->client instanceof Client) {
            return [];
        }

        return [
            Breadcrumb::make($this->realm->name, route('admin.realms.users', ['realm' => $this->realm->slug], false)),
            Breadcrumb::make(__('navigation.clients'), route('admin.realms.clients', ['realm' => $this->realm->slug], false)),
            Breadcrumb::make($this->client->name, route('admin.realms.clients.show', ['realm' => $this->realm->slug, 'client' => $this->client->id], false)),
        ];
    }

    public function render(PageSchema $schema, Realm $realm, Client $client, Request $request): PageSchema
    {
        abort_unless($client->realm_id === $realm->slug, 404);

        $this->realm = $realm;
        $this->client = $client;
        $context = ['realm' => $realm->slug, 'client' => $client->id];
        $secret = $request->session()->get(self::SECRET_FLASH_KEY);

        return $schema->schema([
            Stack::make('admin-client-detail-page')
                ->gap(Gap::Large)
                ->width(Width::Large)
                ->schema([
                    PageHeader::make($client->name, __('admin.clients.detail.description', ['realm' => $realm->name])),
                    ...(is_string($secret) ? [$this->secretCard($client, $secret)] : []),
                    $this->summaryCard($client),
                    Card::make(__('admin.clients.detail.settings.heading'), __('admin.clients.detail.settings.subtitle'))
                        ->schema([Form::use(UpdateClientForm::class, $context)]),
                    Card::make(__('admin.clients.detail.credentials.heading'), __('admin.clients.detail.credentials.subtitle'))
                        ->visible($client->confidential())
                        ->schema([Action::use(RotateClientSecret::class, $context)]),
                    Card::make(__('admin.clients.detail.danger.heading'), __('admin.clients.detail.danger.subtitle'))->schema([
                        Stack::make()
                            ->direction(Orientation::Horizontal)
                            ->gap(Gap::Small)
                            ->schema([
                                Action::use(RevokeClient::class, $context)->visible(! $client->revoked),
                                Action::use(RestoreClient::class, $context)->visible($client->revoked),
                                Action::use(DeleteClient::class, $context),
                            ]),
                    ]),
                ]),
        ]);
    }

    private function secretCard(Client $client, string $secret): Card
    {
        return Card::make(__('admin.clients.detail.secret.heading'), __('admin.clients.detail.secret.subtitle'))->schema([
            Stack::make('client-secret')->gap(Gap::Small)->schema([
                Text::make(__('admin.clients.columns.client-id'))->size(Size::Sm)->color(ColorName::Muted),
                CodeBlock::make($client->client_id, 'client-secret-client-id')->copyable(),
                Text::make(__('admin.clients.detail.secret.label'))->size(Size::Sm)->color(ColorName::Muted),
                CodeBlock::make($secret, 'client-secret-value')->copyable(),
            ]),
        ]);
    }

    private function summaryCard(Client $client): Card
    {
        return Card::make(__('admin.clients.detail.summary.heading'))->schema([
            DescriptionList::make('client-summary')->bleed()->schema([
                ComponentEntry::make('client_id', __('admin.clients.columns.client-id'))
                    ->value(Text::make($client->client_id)->copyable()),
                TextEntry::make('type', __('admin.clients.columns.type'))
                    ->value(__('admin.clients.types.'.str_replace('_', '-', $client->token_endpoint_auth_method->value))),
                TextEntry::make('grant_types', __('admin.clients.columns.grant-types'))
                    ->value(implode(', ', array_map(
                        fn (string $grantType): string => __('admin.clients.grant-types.'.str_replace('_', '-', $grantType)),
                        $client->grant_types,
                    ))),
                ComponentEntry::make('status', __('admin.clients.columns.status'))
                    ->value($client->revoked
                        ? Badge::make(__('admin.clients.status.blocked'))->color(ColorName::Danger)
                        : Badge::make(__('admin.clients.status.active'))->color(ColorName::Success)),
                TextEntry::make('scopes', __('admin.clients.detail.scopes'))
                    ->value(implode(', ', $client->assignedScopes()) ?: '—'),
                DateEntry::make('created_at', __('admin.clients.detail.created-at'))
                    ->value($client->created_at?->toIso8601String())
                    ->style(DateTimeStyle::Long),
            ]),
        ]);
    }
}

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
use App\Shared\Ui\Components\ActionBar;
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
use Lattice\Ui\Components\Component;
use Lattice\Ui\Components\DescriptionList;
use Lattice\Ui\Components\Entries\BadgeEntry;
use Lattice\Ui\Components\Entries\ComponentEntry;
use Lattice\Ui\Components\Entries\DateEntry;
use Lattice\Ui\Components\Entries\TextEntry;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Components\Text;
use Lattice\Ui\Enums\Align;
use Lattice\Ui\Enums\DateTimeStyle;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\Enums\Justify;
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
     * A client secret is readable exactly once, on the page after the request
     * that minted it; the column only holds the hash.
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
                    PageHeader::make(
                        $client->name,
                        __('admin.clients.detail.description', ['realm' => $realm->name]),
                        $this->headerActions($client, $context),
                    ),
                    ...(is_string($secret) ? [$this->secretCard($client, $secret)] : []),
                    $this->settingsCard($client, $context),
                ]),
        ]);
    }

    /**
     * Rotating the secret is what an operator comes here for; the rest sits
     * behind the menu.
     *
     * @param  array<string, string>  $context
     * @return array<int, Component>
     */
    private function headerActions(Client $client, array $context): array
    {
        return ActionBar::make(
            primary: $client->confidential() ? [Action::use(RotateClientSecret::class, $context)] : [],
            overflow: [
                ...($client->revoked
                    ? [Action::use(RestoreClient::class, $context)]
                    : [Action::use(RevokeClient::class, $context)]),
                Action::use(DeleteClient::class, $context),
            ],
            key: 'admin-client',
        );
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

    /**
     * @param  array<string, string>  $context
     */
    private function settingsCard(Client $client, array $context): Card
    {
        return Card::make(__('admin.clients.detail.settings.heading'), __('admin.clients.detail.settings.subtitle'))->schema([
            DescriptionList::make('client-settings')->bleed()->schema([
                TextEntry::make('name', __('admin.clients.fields.name.label'), 'client-name')
                    ->value($client->name)
                    ->disclosure([Form::use(UpdateClientForm::class, [...$context, 'field' => 'name'])]),
                ComponentEntry::make('client_id', __('admin.clients.columns.client-id'), 'client-client-id')
                    ->value(Text::make($client->client_id)->copyable()),
                TextEntry::make('token_endpoint_auth_method', __('admin.clients.columns.type'), 'client-type')
                    ->value(__('admin.clients.types.'.str_replace('_', '-', $client->token_endpoint_auth_method->value)))
                    ->disclosure([Form::use(UpdateClientForm::class, [...$context, 'field' => 'token_endpoint_auth_method'])]),
                ComponentEntry::make('grant_types', __('admin.clients.columns.grant-types'), 'client-grant-types')
                    ->value($this->badges(array_map(
                        fn (string $grantType): string => __('admin.clients.grant-types.'.str_replace('_', '-', $grantType)),
                        $client->grant_types,
                    )))
                    ->disclosure([Form::use(UpdateClientForm::class, [...$context, 'field' => 'grant_types'])]),
                ComponentEntry::make('redirect_uris', __('admin.clients.fields.redirect-uris.label'), 'client-redirect-uris')
                    ->value($this->uriLines($client->redirect_uris))
                    ->disclosure([Form::use(UpdateClientForm::class, [...$context, 'field' => 'redirect_uris'])]),
                ComponentEntry::make('post_logout_redirect_uris', __('admin.clients.fields.post-logout-redirect-uris.label'), 'client-post-logout-redirect-uris')
                    ->value($this->uriLines($client->post_logout_redirect_uris))
                    ->disclosure([Form::use(UpdateClientForm::class, [...$context, 'field' => 'post_logout_redirect_uris'])]),
                BadgeEntry::make('consent_required', __('admin.clients.fields.consent-required.label'), 'client-consent-required')
                    ->value($client->consent_required ? __('common.value.yes') : __('common.value.no'))
                    ->color($client->consent_required ? ColorName::Success : ColorName::Muted)
                    ->disclosure([Form::use(UpdateClientForm::class, [...$context, 'field' => 'consent_required'])]),
                TextEntry::make('backchannel_logout_uri', __('admin.clients.fields.backchannel-logout-uri.label'), 'client-backchannel-logout-uri')
                    ->value($client->backchannel_logout_uri)
                    ->placeholder(__('common.value.none'))
                    ->disclosure([Form::use(UpdateClientForm::class, [...$context, 'field' => 'backchannel_logout_uri'])]),
                ComponentEntry::make('scopes', __('admin.clients.detail.scopes'), 'client-scopes')
                    ->value($this->badges($client->assignedScopes())),
                BadgeEntry::make('status', __('admin.clients.columns.status'), 'client-status')
                    ->value($client->revoked ? __('admin.clients.status.blocked') : __('admin.clients.status.active'))
                    ->color($client->revoked ? ColorName::Danger : ColorName::Success),
                DateEntry::make('created_at', __('admin.clients.detail.created-at'), 'client-created-at')
                    ->value($client->created_at?->toIso8601String())
                    ->style(DateTimeStyle::Long),
            ]),
        ]);
    }

    /**
     * @param  array<int, string>  $values
     */
    private function badges(array $values): Component
    {
        if ($values === []) {
            return Text::make(__('common.value.none'))->color(ColorName::Muted);
        }

        return Stack::make()
            ->direction(Orientation::Horizontal)
            ->width(Width::Auto)
            ->align(Align::Center)
            ->justify(Justify::End)
            ->gap(Gap::ExtraSmall)
            ->schema(array_map(fn (string $value): Badge => Badge::make($value), array_values($values)));
    }

    /**
     * @param  array<int, string>|null  $uris
     */
    private function uriLines(?array $uris): Component
    {
        if ($uris === null || $uris === []) {
            return Text::make(__('common.value.none'))->color(ColorName::Muted);
        }

        return Stack::make()
            ->width(Width::Auto)
            ->align(Align::End)
            ->gap(Gap::ExtraSmall)
            ->schema(array_map(
                fn (string $uri): Text => Text::make($uri)->size(Size::Sm),
                array_values($uris),
            ));
    }
}

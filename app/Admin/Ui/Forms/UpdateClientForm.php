<?php
declare(strict_types=1);

namespace App\Admin\Ui\Forms;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Concerns\BuildsClientFields;
use App\Admin\Ui\Concerns\ResolvesRealmClient;
use App\Admin\Ui\Pages\ClientDetailPage;
use App\Shared\Realms\Contracts\UpdatesClient;
use Bambamboole\LaravelOidc\Server\Clients\Models\Client;
use Illuminate\Http\Request;
use Lattice\Facades\Effects;
use Lattice\Form\Attributes\AsForm;
use Lattice\Form\Components\Form as FormComponent;
use Lattice\Form\Components\TextInput;
use Lattice\Form\Components\Toggle;
use Lattice\Form\FormData;
use Lattice\Form\FormDefinition;
use Lattice\Http\LatticeResponse;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsForm('admin.clients.update', can: [AdminPermission::Access, AdminPermission::ManageRealms])]
final class UpdateClientForm extends FormDefinition
{
    use BuildsClientFields;
    use ResolvesRealmClient;

    public function __construct(private readonly UpdatesClient $updateClient) {}

    #[\Override]
    public function authorize(Request $request): bool
    {
        return $this->realmClientOrNull() instanceof Client;
    }

    public function definition(FormComponent $form, Request $request): FormComponent
    {
        $client = $this->realmClient();

        return $form
            ->method(HttpMethod::Patch)
            ->schema([
                ...$this->clientFields($client),
                Toggle::make('consent_required', __('admin.clients.fields.consent-required.label'))
                    ->value($client->consent_required, editable: true)
                    ->helperText(__('admin.clients.fields.consent-required.help-text'))
                    ->rules(['boolean']),
                TextInput::make('backchannel_logout_uri', __('admin.clients.fields.backchannel-logout-uri.label'))
                    ->value($client->backchannel_logout_uri, editable: true)
                    ->helperText(__('admin.clients.fields.backchannel-logout-uri.help-text'))
                    ->rules(['nullable', 'string', 'max:2048', 'url:http,https']),
            ])
            ->submitLabel(__('common.action.save'));
    }

    public function handle(FormData $data): LatticeResponse
    {
        $realm = $this->realm();
        $client = $this->realmClient();
        $backchannelLogoutUri = trim((string) $data->string('backchannel_logout_uri'));

        $secret = $this->updateClient->handle($client, $this->clientAttributes(
            $data,
            consentRequired: $data->boolean('consent_required'),
            backchannelLogoutUri: $backchannelLogoutUri === '' ? null : $backchannelLogoutUri,
        ));

        ClientDetailPage::revealSecret($secret);

        return Effects::respond()->toast(__('admin.clients.updated'), Variant::Success)
            ->toRoute('admin.realms.clients.show', ['realm' => $realm->slug, 'client' => $client->id]);
    }
}

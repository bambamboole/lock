<?php
declare(strict_types=1);

namespace App\Clients\Ui\Forms;

use App\Clients\Ui\Concerns\BuildsClientFields;
use App\Clients\Ui\Concerns\ResolvesRealmClient;
use App\Clients\Ui\Pages\ClientDetailPage;
use App\Shared\Api\Enums\ApiScope;
use App\Shared\Clients\Contracts\CreatesClient;
use Illuminate\Http\Request;
use Lattice\Facades\Effects;
use Lattice\Form\Attributes\AsForm;
use Lattice\Form\Components\Form as FormComponent;
use Lattice\Form\FormData;
use Lattice\Form\FormDefinition;
use Lattice\Http\LatticeResponse;
use Lattice\Ui\Enums\Variant;

#[AsForm('admin.clients.create', can: ApiScope::ClientsWrite)]
final class CreateClientForm extends FormDefinition
{
    use BuildsClientFields;
    use ResolvesRealmClient;

    public function __construct(private readonly CreatesClient $createClient) {}

    public function definition(FormComponent $form, Request $request): FormComponent
    {
        $this->realm();

        return $form
            ->schema($this->clientCreationFields())
            ->submitLabel(__('admin.clients.create.submit'));
    }

    public function handle(FormData $data): LatticeResponse
    {
        $realm = $this->realm();
        $client = $this->createClient->handle($realm, $this->clientAttributes($data, null));

        ClientDetailPage::revealSecret($client->plainSecret);

        return Effects::respond()->toast(__('admin.clients.created'), Variant::Success)
            ->toRoute('admin.realms.clients.show', ['realm' => $realm->slug, 'client' => $client->id]);
    }
}

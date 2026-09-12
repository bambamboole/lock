<?php
declare(strict_types=1);

namespace App\Clients\Ui\Actions;

use App\Clients\Ui\Concerns\ResolvesRealmClient;
use App\Clients\Ui\Pages\ClientDetailPage;
use App\Shared\Authorization\Enums\Scope;
use App\Shared\Clients\Contracts\RotatesClientSecret;
use Bambamboole\LaravelOidc\Server\Clients\Models\Client;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Ui\Enums\Variant;

#[AsAction('admin.clients.rotate-secret', can: Scope::ClientsWrite)]
final class RotateClientSecret extends ActionDefinition
{
    use ResolvesRealmClient;

    public function __construct(private readonly RotatesClientSecret $rotateSecret) {}

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('admin.clients.detail.rotate-secret.label'))
            ->confirm(__('admin.clients.detail.rotate-secret.confirm.title'), __('admin.clients.detail.rotate-secret.confirm.description'));
    }

    #[\Override]
    public function authorize(Request $request): bool
    {
        $client = $this->realmClientOrNull();

        return $client instanceof Client && $client->confidential();
    }

    public function handle(Request $request): ActionResult
    {
        $client = $this->realmClient();

        ClientDetailPage::revealSecret($this->rotateSecret->handle($client));

        return ActionResult::success()
            ->toast(__('admin.clients.detail.rotate-secret.done'), Variant::Success)
            ->toRoute('admin.realms.clients.show', ['realm' => $this->realm()->slug, 'client' => $client->id]);
    }
}

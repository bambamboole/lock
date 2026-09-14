<?php
declare(strict_types=1);

namespace App\Clients\Ui\Actions;

use App\Clients\Ui\Concerns\ResolvesRealmClient;
use App\Shared\Authorization\Enums\Scope;
use App\Shared\Clients\Contracts\DeletesClient;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;
use Lock\Server\Clients\Models\Client;

#[AsAction('admin.clients.delete', can: Scope::ClientsWrite)]
final class DeleteClient extends ActionDefinition
{
    use ResolvesRealmClient;

    public function __construct(private readonly DeletesClient $deleteClient) {}

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('admin.clients.detail.delete.label'))
            ->method(HttpMethod::Delete)
            ->confirm(__('admin.clients.detail.delete.confirm.title'), __('admin.clients.detail.delete.confirm.description'));
    }

    #[\Override]
    public function authorize(Request $request): bool
    {
        return $this->realmClientOrNull() instanceof Client;
    }

    public function handle(Request $request): ActionResult
    {
        $this->deleteClient->handle($this->realmClient());

        return ActionResult::success()
            ->toast(__('admin.clients.deleted'), Variant::Success)
            ->toRoute('admin.realms.clients', ['realm' => $this->realm()->slug]);
    }
}

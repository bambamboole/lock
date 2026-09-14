<?php
declare(strict_types=1);

namespace App\Clients\Ui\Actions;

use App\Clients\Ui\Concerns\ResolvesRealmClient;
use App\Shared\Authorization\Enums\Scope;
use App\Shared\Clients\Contracts\SetsClientRevocation;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Ui\Enums\Variant;
use Lock\Server\Clients\Models\Client;

#[AsAction('admin.clients.revoke', can: Scope::ClientsWrite)]
final class RevokeClient extends ActionDefinition
{
    use ResolvesRealmClient;

    public function __construct(private readonly SetsClientRevocation $setRevocation) {}

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('admin.clients.detail.revoke.label'))
            ->confirm(__('admin.clients.detail.revoke.confirm.title'), __('admin.clients.detail.revoke.confirm.description'));
    }

    #[\Override]
    public function authorize(Request $request): bool
    {
        $client = $this->realmClientOrNull();

        return $client instanceof Client && ! $client->snapshot()->revoked;
    }

    public function handle(Request $request): ActionResult
    {
        $this->setRevocation->handle($this->realmClient(), true);

        return ActionResult::success()
            ->toast(__('admin.clients.detail.revoke.done'), Variant::Success)
            ->reloadPage();
    }
}

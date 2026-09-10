<?php
declare(strict_types=1);

namespace App\Admin\Ui\Actions;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Concerns\ResolvesRealmClient;
use App\Shared\Realms\Contracts\DeletesClient;
use Bambamboole\LaravelOidc\Server\Clients\Models\Client;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsAction('admin.clients.delete', can: [AdminPermission::Access, AdminPermission::ManageRealms])]
final class DeleteClient extends ActionDefinition
{
    use ResolvesRealmClient;

    public function __construct(private readonly DeletesClient $deleteClient) {}

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('admin.clients.detail.delete.label'))
            ->method(HttpMethod::Delete)
            ->variant(Variant::Danger)
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

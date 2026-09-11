<?php
declare(strict_types=1);

namespace App\Admin\Ui\Actions;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Concerns\ResolvesRealmResource;
use App\Resources\Models\Resource;
use App\Shared\Resources\Contracts\DeletesResource;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Ui\Enums\Emphasis;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsAction('admin.resources.delete', can: [AdminPermission::Access, AdminPermission::ManageResources])]
final class DeleteResourceAction extends ActionDefinition
{
    use ResolvesRealmResource;

    public function __construct(private readonly DeletesResource $deleteResource) {}

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('admin.resources.delete.label'))
            ->method(HttpMethod::Delete)
            ->variant(Variant::Danger)
            ->emphasis(Emphasis::Ghost)
            ->confirm(__('admin.resources.delete.confirm.title'), __('admin.resources.delete.confirm.description'));
    }

    #[\Override]
    public function authorize(Request $request): bool
    {
        return $this->resourceOrNull() instanceof Resource;
    }

    public function handle(Request $request): ActionResult
    {
        $realm = $this->realm();

        $this->deleteResource->handle($this->resource());

        return ActionResult::success()
            ->toast(__('admin.resources.deleted'), Variant::Success)
            ->toRoute('admin.realms.resources', ['realm' => $realm->slug]);
    }
}

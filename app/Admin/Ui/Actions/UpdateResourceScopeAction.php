<?php
declare(strict_types=1);

namespace App\Admin\Ui\Actions;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Concerns\BuildsResourceScopeFields;
use App\Admin\Ui\Concerns\ResolvesRealmResource;
use App\Admin\Ui\Tables\AdminResourceScopesTable;
use App\Resources\Models\ResourceScope;
use App\Shared\Resources\Contracts\UpdatesResourceScope;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Form\FormData;
use Lattice\Ui\Enums\Emphasis;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsAction('admin.resource-scopes.update', can: [AdminPermission::Access, AdminPermission::ManageResources])]
final class UpdateResourceScopeAction extends ActionDefinition
{
    use BuildsResourceScopeFields;
    use ResolvesRealmResource;

    public function __construct(private readonly UpdatesResourceScope $updateScope) {}

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('admin.resources.scopes.edit.label'))
            ->method(HttpMethod::Patch)
            ->emphasis(Emphasis::Ghost)
            ->form($this->resourceScopeFields($this->resource(), $this->resourceScope()));
    }

    #[\Override]
    public function authorize(Request $request): bool
    {
        return $this->mutableResourceScopeOrNull() instanceof ResourceScope;
    }

    public function handle(FormData $data): ActionResult
    {
        $description = trim((string) $data->string('description'));

        $this->updateScope->handle(
            $this->resourceScope(),
            (string) $data->string('value'),
            $description === '' ? null : $description,
        );

        return ActionResult::success()
            ->toast(__('admin.resources.scopes.updated'), Variant::Success)
            ->reloadComponent(AdminResourceScopesTable::ID);
    }
}

<?php
declare(strict_types=1);

namespace App\Resources\Ui\Actions;

use App\Resources\Models\Resource;
use App\Resources\Ui\Concerns\BuildsResourceScopeFields;
use App\Resources\Ui\Concerns\ResolvesRealmResource;
use App\Resources\Ui\Tables\ResourceScopesTable;
use App\Shared\Authorization\Enums\Scope;
use App\Shared\Resources\Contracts\CreatesResourceScope;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Form\FormData;
use Lattice\Ui\Enums\Variant;

#[AsAction('admin.resource-scopes.create', can: Scope::ResourcesWrite)]
final class CreateResourceScopeAction extends ActionDefinition
{
    use BuildsResourceScopeFields;
    use ResolvesRealmResource;

    public function __construct(private readonly CreatesResourceScope $createScope) {}

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('admin.resources.scopes.create.label'))
            ->form($this->resourceScopeFields($this->resource(), null));
    }

    #[\Override]
    public function authorize(Request $request): bool
    {
        return $this->mutableResourceOrNull() instanceof Resource;
    }

    public function handle(FormData $data): ActionResult
    {
        $description = trim((string) $data->string('description'));

        $this->createScope->handle(
            $this->resource(),
            (string) $data->string('value'),
            $description === '' ? null : $description,
        );

        return ActionResult::success()
            ->toast(__('admin.resources.scopes.created'), Variant::Success)
            ->reloadComponent(ResourceScopesTable::ID);
    }
}

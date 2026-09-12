<?php
declare(strict_types=1);

namespace App\Resources\Ui\Actions;

use App\Resources\Models\Resource;
use App\Resources\Ui\Concerns\BuildsResourceFields;
use App\Resources\Ui\Concerns\ResolvesRealmResource;
use App\Resources\Ui\Tables\ResourcesTable;
use App\Shared\Authorization\Enums\Scope;
use App\Shared\Resources\Contracts\UpdatesResource;
use App\Shared\Resources\Data\UpdateResourceData;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Form\FormData;
use Lattice\Ui\Enums\Emphasis;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsAction('admin.resources.update', can: Scope::ResourcesWrite)]
final class UpdateResourceAction extends ActionDefinition
{
    use BuildsResourceFields;
    use ResolvesRealmResource;

    public function __construct(private readonly UpdatesResource $updateResource) {}

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('admin.resources.edit.label'))
            ->method(HttpMethod::Patch)
            ->emphasis(Emphasis::Ghost)
            ->form($this->resourceFields($this->realm(), $this->resource()));
    }

    #[\Override]
    public function authorize(Request $request): bool
    {
        return $this->mutableResourceOrNull() instanceof Resource;
    }

    public function handle(FormData $data): ActionResult
    {
        $this->updateResource->handle(
            $this->resource(),
            new UpdateResourceData(
                identifier: trim((string) $data->string('identifier')),
                name: (string) $data->string('name'),
            ),
        );

        return ActionResult::success()
            ->toast(__('admin.resources.updated'), Variant::Success)
            ->reloadComponent(ResourcesTable::ID)
            ->reloadPage();
    }
}

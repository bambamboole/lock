<?php
declare(strict_types=1);

namespace App\Resources\Ui\Actions;

use App\Resources\Ui\Concerns\BuildsResourceFields;
use App\Resources\Ui\Concerns\ResolvesRealmResource;
use App\Resources\Ui\Tables\ResourcesTable;
use App\Shared\Authorization\Enums\Scope;
use App\Shared\Resources\Contracts\CreatesResource;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Form\FormData;
use Lattice\Ui\Enums\Variant;

#[AsAction('admin.resources.create', can: Scope::ResourcesWrite)]
final class CreateResourceAction extends ActionDefinition
{
    use BuildsResourceFields;
    use ResolvesRealmResource;

    public function __construct(private readonly CreatesResource $createResource) {}

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('admin.resources.create.label'))
            ->form($this->resourceFields($this->realm(), null));
    }

    public function handle(FormData $data): ActionResult
    {
        $this->createResource->handle(
            $this->realm(),
            trim((string) $data->string('identifier')),
            (string) $data->string('name'),
        );

        return ActionResult::success()
            ->toast(__('admin.resources.created'), Variant::Success)
            ->reloadComponent(ResourcesTable::ID);
    }
}

<?php
declare(strict_types=1);

namespace App\Admin\Ui\Forms;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Concerns\BuildsResourceFields;
use App\Admin\Ui\Concerns\ResolvesRealmResource;
use App\Resources\Models\Resource;
use App\Shared\Resources\Contracts\UpdatesResource;
use Illuminate\Http\Request;
use Lattice\Facades\Effects;
use Lattice\Form\Attributes\AsForm;
use Lattice\Form\Components\Field;
use Lattice\Form\Components\Form as FormComponent;
use Lattice\Form\FormData;
use Lattice\Form\FormDefinition;
use Lattice\Http\LatticeResponse;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

/**
 * One row of the detail page at a time; the field the form does not carry
 * keeps the value the resource already holds.
 */
#[AsForm('admin.resources.update-field', can: [AdminPermission::Access, AdminPermission::ManageResources])]
final class UpdateResourceForm extends FormDefinition
{
    use BuildsResourceFields;
    use ResolvesRealmResource;

    public function __construct(private readonly UpdatesResource $updateResource) {}

    #[\Override]
    public function authorize(Request $request): bool
    {
        return $this->resourceOrNull() instanceof Resource;
    }

    public function definition(FormComponent $form, Request $request): FormComponent
    {
        return $form
            ->method(HttpMethod::Patch)
            ->schema([$this->field()])
            ->submitLabel(__('common.action.save'));
    }

    public function handle(FormData $data): LatticeResponse
    {
        $resource = $this->resource();
        $field = $this->fieldName();

        $this->updateResource->handle(
            $resource,
            $field === 'identifier' ? trim((string) $data->string('identifier')) : $resource->identifier,
            $field === 'name' ? (string) $data->string('name') : $resource->name,
        );

        return Effects::respond()->toast(__('admin.resources.updated'), Variant::Success)
            ->toRoute('admin.realms.resources.show', ['realm' => $this->realm()->slug, 'resource' => $resource->id]);
    }

    private function field(): Field
    {
        return $this->resourceFields($this->realm(), $this->resource())[$this->fieldName()];
    }

    private function fieldName(): string
    {
        $field = $this->contextString('field');

        abort_unless(array_key_exists($field, $this->resourceFields($this->realm(), $this->resource())), 404);

        return $field;
    }
}

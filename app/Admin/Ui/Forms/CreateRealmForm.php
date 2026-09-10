<?php
declare(strict_types=1);

namespace App\Admin\Ui\Forms;

use App\Admin\Enums\AdminPermission;
use App\Shared\Realms\Contracts\CreatesRealm;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Lattice\Facades\Effects;
use Lattice\Form\Attributes\AsForm;
use Lattice\Form\Components\Form as FormComponent;
use Lattice\Form\Components\TextInput;
use Lattice\Form\FormData;
use Lattice\Form\FormDefinition;
use Lattice\Http\LatticeResponse;
use Lattice\Ui\Enums\Variant;

#[AsForm('admin.realms.create', can: [AdminPermission::Access, AdminPermission::ManageRealms])]
final class CreateRealmForm extends FormDefinition
{
    public function __construct(private readonly CreatesRealm $createRealm) {}

    public function definition(FormComponent $form, Request $request): FormComponent
    {
        return $form->schema([
            TextInput::make('name', __('common.field.name'))
                ->required()->rules(['string', 'max:255']),
            TextInput::make('slug', __('admin.realms.fields.slug.label'))
                ->helperText(__('admin.realms.fields.slug.help-text'))
                ->required()->rules(['string', 'max:63', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', Rule::unique('realms', 'slug')]),
        ])->submitLabel(__('admin.realms.create.submit'));
    }

    public function handle(FormData $data): LatticeResponse
    {
        $realm = $this->createRealm->handle((string) $data->string('name'), (string) $data->string('slug'));

        return Effects::respond()->toast(__('admin.realms.created'), Variant::Success)
            ->toRoute('realm.settings.edit', ['current_realm' => $realm->slug, 'tabs' => 'configuration']);
    }
}

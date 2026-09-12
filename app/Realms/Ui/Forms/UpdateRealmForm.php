<?php
declare(strict_types=1);

namespace App\Realms\Ui\Forms;

use App\Realms\Models\Realm;
use App\Shared\Authorization\Enums\Scope;
use App\Shared\Realms\Contracts\UpdatesRealm;
use App\Shared\Realms\Data\UpdateRealmData;
use Illuminate\Http\Request;
use Lattice\Facades\Effects;
use Lattice\Form\Attributes\AsForm;
use Lattice\Form\Components\Form as FormComponent;
use Lattice\Form\Components\TextInput;
use Lattice\Form\FormData;
use Lattice\Form\FormDefinition;
use Lattice\Http\LatticeResponse;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsForm('admin.realms.update', can: Scope::RealmsWrite)]
final class UpdateRealmForm extends FormDefinition
{
    public function __construct(private readonly UpdatesRealm $updateRealm) {}

    public function definition(FormComponent $form, Request $request): FormComponent
    {
        $realm = $this->realm();

        return $form
            ->method(HttpMethod::Patch)
            ->schema([
                TextInput::make('name', __('admin.realms.fields.name.label'))
                    ->value($realm->name, editable: true)
                    ->required()
                    ->rules(['string', 'max:'.Realm::NAME_MAX_LENGTH]),
            ])
            ->submitLabel(__('admin.realms.general.submit'));
    }

    public function handle(FormData $data): LatticeResponse
    {
        $realm = $this->realm();

        $this->updateRealm->handle($realm, UpdateRealmData::from(['name' => (string) $data->string('name')]));

        return Effects::respond()->toast(__('admin.realms.updated'), Variant::Success)
            ->toRoute('admin.realms.settings', ['realm' => $realm->slug]);
    }

    private function realm(): Realm
    {
        return $this->contextModel('realm', Realm::class);
    }
}

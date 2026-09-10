<?php
declare(strict_types=1);

namespace App\Admin\Ui\Forms;

use App\Admin\Enums\AdminPermission;
use App\Realms\Models\Realm;
use App\Shared\Realms\Contracts\UpdatesRealmConfiguration;
use App\Shared\Realms\Support\RealmConfiguration;
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

#[AsForm('admin.realms.update', can: [AdminPermission::Access, AdminPermission::ManageRealms])]
final class UpdateRealmForm extends FormDefinition
{
    public function __construct(private readonly UpdatesRealmConfiguration $updateRealm) {}

    public function definition(FormComponent $form, Request $request): FormComponent
    {
        $realm = $this->realm();

        return $form
            ->method(HttpMethod::Patch)
            ->schema([
                TextInput::make('name', __('admin.realms.fields.name.label'))
                    ->value($realm->name, editable: true)
                    ->required()
                    ->rules(RealmConfiguration::rules()['name']),
            ])
            ->submitLabel(__('admin.realms.general.submit'));
    }

    public function handle(FormData $data): LatticeResponse
    {
        $realm = $this->realm();

        $this->updateRealm->handle($realm, ['name' => (string) $data->string('name')]);

        return Effects::respond()->toast(__('admin.realms.updated'), Variant::Success)
            ->toRoute('admin.realms.show', ['realm' => $realm->slug]);
    }

    private function realm(): Realm
    {
        $realm = $this->contextModel('realm');

        abort_unless($realm instanceof Realm, 404);

        return $realm;
    }
}

<?php
declare(strict_types=1);

namespace App\Realms\Ui\Forms;

use App\Realms\Enums\RealmAuditEvent;
use App\Realms\Enums\RealmPermission;
use App\Realms\Models\Realm;
use App\Realms\Support\Audit;
use App\Realms\Ui\Concerns\ActivatesRealmContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Lattice\Facades\Effects;
use Lattice\Form\Attributes\AsForm;
use Lattice\Form\Components\Form as FormComponent;
use Lattice\Form\Components\TextInput;
use Lattice\Form\FormData;
use Lattice\Form\FormDefinition;
use Lattice\Http\LatticeResponse;
use Lattice\Ui\Components\Grid;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsForm('realms.update', can: RealmPermission::UpdateRealm, on: 'realm')]
final class UpdateRealmForm extends FormDefinition
{
    use ActivatesRealmContext;

    public function definition(FormComponent $form, Request $request): FormComponent
    {
        /** @var Realm $realm */
        $realm = $this->contextModel('realm');

        return $form
            ->method(HttpMethod::Patch)
            ->schema([
                Grid::make('realms-update-fields')
                    ->columns(1)
                    ->schema([
                        TextInput::make('name', __('realm.fields.name'))
                            ->value($realm->name)
                            ->placeholder(__('realm.fields.name'))
                            ->required()
                            ->rules(['string', 'max:255']),
                    ]),
            ])
            ->submitLabel(__('realm.update.submit'));
    }

    public function handle(FormData $data): LatticeResponse
    {
        /** @var Realm $realm */
        $realm = $this->contextModel('realm');

        $oldName = $realm->name;

        $realm = DB::transaction(function () use ($realm, $data): Realm {
            $realm = Realm::whereKey($realm->id)->lockForUpdate()->firstOrFail();
            $realm->update(['name' => (string) $data['name']]);

            return $realm;
        });

        Audit::record(RealmAuditEvent::RealmUpdated, $realm, $realm, [
            'name' => ['from' => $oldName, 'to' => $realm->name],
        ]);

        return Effects::respond()->toast(__('realm.update.updated'), Variant::Success)->toRoute('realm.settings.edit');
    }
}

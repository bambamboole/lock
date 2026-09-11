<?php
declare(strict_types=1);

namespace App\Admin\Ui\Forms;

use App\Admin\Enums\AdminPermission;
use App\Realms\Models\Realm;
use App\Shared\Realms\Contracts\ChecksRealmDomain;
use App\Shared\Realms\Contracts\UpdatesRealmDomain;
use App\Shared\Realms\Enums\RealmDomainStatus;
use App\Shared\Realms\Support\RealmDomain;
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

#[AsForm('admin.realms.domain.update', can: [AdminPermission::Access, AdminPermission::ManageRealms])]
final class UpdateRealmDomainForm extends FormDefinition
{
    public function __construct(
        private readonly UpdatesRealmDomain $updateDomain,
        private readonly ChecksRealmDomain $checkDomain,
    ) {}

    #[\Override]
    public function authorize(Request $request): bool
    {
        $realm = $this->contextModelOrNull('realm');

        return $realm instanceof Realm && ! $realm->isMaster();
    }

    public function definition(FormComponent $form, Request $request): FormComponent
    {
        $realm = $this->realm();

        return $form
            ->method(HttpMethod::Patch)
            ->schema([
                TextInput::make('domain', __('admin.realms.fields.domain.label'))
                    ->value($realm->domain, editable: true)
                    ->helperText(__('admin.realms.domain.change-warning'))
                    ->required()
                    ->rules(RealmDomain::rules($realm)),
            ])
            ->submitLabel(__('admin.realms.domain.submit'));
    }

    public function handle(FormData $data): LatticeResponse
    {
        $realm = $this->realm();
        $before = $realm->domain;

        $this->updateDomain->handle($realm, (string) $data->string('domain'));

        if ($realm->domain === $before) {
            return Effects::respond()->toRoute('admin.realms.settings', ['realm' => $realm->slug]);
        }

        $status = $this->checkDomain->handle($realm);
        $outcome = __('admin.realms.domain.outcome.'.$status->value, ['domain' => $realm->domain]);

        return Effects::respond()
            ->toast(__('admin.realms.domain.updated').' '.$outcome, $status === RealmDomainStatus::Verified ? Variant::Success : Variant::Warning)
            ->toRoute('admin.realms.settings', ['realm' => $realm->slug]);
    }

    private function realm(): Realm
    {
        $realm = $this->contextModel('realm');

        abort_unless($realm instanceof Realm, 404);

        return $realm;
    }
}

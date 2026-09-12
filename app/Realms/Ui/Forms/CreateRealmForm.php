<?php
declare(strict_types=1);

namespace App\Realms\Ui\Forms;

use App\Realms\Models\Realm;
use App\Shared\Api\Enums\ApiScope;
use App\Shared\Realms\Contracts\ChecksRealmDomain;
use App\Shared\Realms\Contracts\CreatesRealm;
use App\Shared\Realms\Data\CreateRealmData;
use App\Shared\Realms\Enums\RealmDomainStatus;
use App\Shared\Realms\Enums\RealmSettingSection;
use App\Shared\Realms\Support\RealmDomain;
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

#[AsForm('admin.realms.create', can: ApiScope::RealmsWrite)]
final class CreateRealmForm extends FormDefinition
{
    public function __construct(
        private readonly CreatesRealm $createRealm,
        private readonly ChecksRealmDomain $checkDomain,
    ) {}

    public function definition(FormComponent $form, Request $request): FormComponent
    {
        return $form->schema([
            TextInput::make('name', __('admin.realms.fields.name.label'))
                ->required()->rules(['string', 'max:'.Realm::NAME_MAX_LENGTH]),
            TextInput::make('slug', __('admin.realms.fields.slug.label'))
                ->helperText(__('admin.realms.fields.slug.help-text'))
                ->required()->rules(['string', 'max:63', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', Rule::unique('realms', 'slug')]),
            TextInput::make('domain', __('admin.realms.fields.domain.label'))
                ->helperText(__('admin.realms.fields.domain.help-text'))
                ->required()->rules(RealmDomain::rules()),
        ])->submitLabel(__('admin.realms.create.submit'));
    }

    /**
     * A domain that does not reach this instance yet is expected, since DNS and
     * the proxy usually follow the realm, so the realm is kept and the outcome
     * reported.
     */
    public function handle(FormData $data): LatticeResponse
    {
        $realm = $this->createRealm->handle(CreateRealmData::from([
            'name' => (string) $data->string('name'),
            'slug' => (string) $data->string('slug'),
            'domain' => (string) $data->string('domain'),
        ]));
        $status = $this->checkDomain->handle($realm);

        if ($status === RealmDomainStatus::Verified) {
            return Effects::respond()->toast(__('admin.realms.created'), Variant::Success)
                ->toRoute('admin.realms.settings', ['realm' => $realm->slug, 'tabs' => RealmSettingSection::Tokens->value]);
        }

        $outcome = __('admin.realms.domain.outcome.'.$status->value, ['domain' => $realm->domain]);

        return Effects::respond()->toast(__('admin.realms.created-unverified', ['outcome' => $outcome]), Variant::Warning)
            ->toRoute('admin.realms.settings', ['realm' => $realm->slug]);
    }
}

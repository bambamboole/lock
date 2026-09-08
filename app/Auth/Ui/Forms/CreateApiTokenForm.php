<?php
declare(strict_types=1);

namespace App\Auth\Ui\Forms;

use App\Auth\Actions\CreateApiToken;
use App\Auth\Support\GrantableTenantScopes;
use App\Auth\Support\PermissionRepository;
use App\Shared\Concerns\ResolvesCurrentUser;
use App\Tenants\Models\Tenant;
use App\Tenants\Ui\Concerns\ActivatesTenantContext;
use Illuminate\Http\Request;
use Lattice\Facades\Effects;
use Lattice\Form\Attributes\AsForm;
use Lattice\Form\Components\CheckboxGroup;
use Lattice\Form\Components\Form as FormComponent;
use Lattice\Form\Components\TextInput;
use Lattice\Form\FormData;
use Lattice\Form\FormDefinition;
use Lattice\Http\LatticeResponse;
use Lattice\Ui\Components\Grid;
use Lattice\Ui\Components\Modal;
use Lattice\Ui\Components\Text;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsForm('tenant.settings.api-tokens.create', can: 'view', on: 'tenant')]
final class CreateApiTokenForm extends FormDefinition
{
    use ActivatesTenantContext;
    use ResolvesCurrentUser;

    public function __construct(
        private readonly CreateApiToken $createApiToken,
        private readonly PermissionRepository $permissions,
        private readonly GrantableTenantScopes $grantableScopes,
    ) {}

    public function definition(FormComponent $form, Request $request): FormComponent
    {
        // Offer only the scopes the create action will accept: a listed scope
        // the user cannot grant would validate into an error on submit.
        /** @var Tenant $tenant */
        $tenant = $this->contextModel('tenant');
        $grantable = $this->grantableScopes->for($this->currentUser(), $tenant);

        $scopeOptions = [];

        foreach ($this->permissions->apiScopes() as $value => $description) {
            if (in_array($value, $grantable, true)) {
                $scopeOptions[] = CheckboxGroup::option($description, $value, description: $value);
            }
        }

        return $form
            ->method(HttpMethod::Post)
            ->schema([
                Grid::make('api-token-fields')
                    ->columns(1)
                    ->schema([
                        TextInput::make('name', __('tenant.settings.api-tokens.name.label'))
                            ->placeholder(__('tenant.settings.api-tokens.name.placeholder'))
                            ->required()
                            ->rules(['string', 'max:255']),
                        CheckboxGroup::make('scopes', __('tenant.settings.api-tokens.scopes'))
                            ->options($scopeOptions)
                            ->helperText(__('tenant.settings.api-tokens.scopes-help')),
                    ]),
            ])
            ->submitLabel(__('tenant.settings.api-tokens.create'));
    }

    public function handle(FormData $data): LatticeResponse
    {
        /** @var Tenant $tenant */
        $tenant = $this->contextModel('tenant');

        $plain = $this->createApiToken->handle(
            $this->currentUser(),
            $tenant,
            (string) $data['name'],
            array_values((array) ($data['scopes'] ?? [])),
        );

        // The token is readable exactly once, so the dialog ships with the effect
        // that reveals it rather than being composed into the page and flipped
        // open from the session on the next render.
        return Effects::respond()->toast(__('tenant.settings.api-tokens.created'), Variant::Success)
            ->openModal(
                Modal::make('tenant.settings.api-tokens.created')
                    ->title(__('tenant.settings.api-tokens.reveal.heading'))
                    ->description(__('tenant.settings.api-tokens.reveal-help'))
                    ->schema([
                        Text::make($plain)->copyable(),
                    ]),
            )
            ->toRoute('tenant.settings.edit', ['current_tenant' => $tenant->slug, 'tabs' => 'api-tokens']);
    }
}

<?php
declare(strict_types=1);

namespace App\Tenants\Ui\Forms;

use App\Shared\Concerns\ResolvesCurrentUser;
use App\Tenants\Actions\DeleteTenant;
use App\Tenants\Models\Tenant;
use App\Tenants\Ui\Concerns\ActivatesTenantContext;
use Closure;
use Illuminate\Http\Request;
use Lattice\Facades\Effects;
use Lattice\Form\Attributes\AsForm;
use Lattice\Form\Components\Form as FormComponent;
use Lattice\Form\Components\TextInput;
use Lattice\Form\FormDefinition;
use Lattice\Http\LatticeResponse;
use Lattice\Ui\Components\Text;
use Lattice\Ui\Enums\Emphasis;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsForm('tenants.delete', can: 'delete', on: 'tenant')]
final class DeleteTenantForm extends FormDefinition
{
    use ActivatesTenantContext;
    use ResolvesCurrentUser;

    public function __construct(private readonly DeleteTenant $deleteTenant) {}

    public function definition(FormComponent $form, Request $request): FormComponent
    {
        /** @var Tenant $tenant */
        $tenant = $this->contextModel('tenant');

        return $form
            ->method(HttpMethod::Delete)
            ->schema([
                Text::make(__('tenant.delete.warning')),
                TextInput::make('name', __('tenant.delete.confirm-name'))
                    ->placeholder($tenant->name)
                    ->required()
                    ->rules(['string'])
                    ->rules(fn (): array => [function (string $attribute, mixed $value, Closure $fail) use ($tenant): void {
                        if ((string) $value !== $tenant->name) {
                            $fail(__('tenant.delete.name-mismatch'));
                        }
                    }]),
            ])
            ->submitLabel(__('tenant.delete.submit'))
            ->submitEmphasis(Emphasis::Outline);
    }

    public function handle(Request $request): LatticeResponse
    {
        /** @var Tenant $tenant */
        $tenant = $this->contextModel('tenant');

        $this->deleteTenant->handle($this->currentUser(), $tenant);

        $currentTenant = $this->currentUser()->refresh()->currentTenant;

        abort_unless($currentTenant instanceof Tenant, 500);

        return Effects::respond()->toast(__('tenant.delete.deleted'), Variant::Success)
            ->toRoute('tenant.settings.edit', ['current_tenant' => $currentTenant->slug]);
    }
}

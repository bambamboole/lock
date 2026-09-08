<?php
declare(strict_types=1);

namespace App\Tenants\Ui\Forms;

use App\Shared\Concerns\ResolvesCurrentUser;
use App\Tenants\Actions\CreateTenant;
use Illuminate\Http\Request;
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

#[AsForm('tenants.create')]
final class CreateTenantForm extends FormDefinition
{
    use ResolvesCurrentUser;

    public function __construct(private readonly CreateTenant $createTenant) {}

    public function definition(FormComponent $form, Request $request): FormComponent
    {
        return $form
            ->method(HttpMethod::Post)
            ->schema([
                Grid::make('tenants-create-fields')
                    ->columns(1)
                    ->schema([
                        TextInput::make('name', __('tenant.fields.name'))
                            ->placeholder(__('tenant.create.name.placeholder'))
                            ->required()
                            ->rules(['string', 'max:255']),
                    ]),
            ])
            ->submitLabel(__('tenant.create.submit'));
    }

    public function handle(FormData $data): LatticeResponse
    {
        $tenant = $this->createTenant->handle($this->currentUser(), (string) $data['name']);

        return Effects::respond()->toast(__('tenant.create.created'), Variant::Success)->toRoute('tenant.settings.edit', ['current_tenant' => $tenant->slug]);
    }
}

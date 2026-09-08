<?php
declare(strict_types=1);

namespace App\Tenants\Ui\Forms;

use App\Tenants\Enums\TenantAuditEvent;
use App\Tenants\Enums\TenantPermission;
use App\Tenants\Models\Tenant;
use App\Tenants\Support\Audit;
use App\Tenants\Ui\Concerns\ActivatesTenantContext;
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

#[AsForm('tenants.update', can: TenantPermission::UpdateTenant, on: 'tenant')]
final class UpdateTenantForm extends FormDefinition
{
    use ActivatesTenantContext;

    public function definition(FormComponent $form, Request $request): FormComponent
    {
        /** @var Tenant $tenant */
        $tenant = $this->contextModel('tenant');

        return $form
            ->method(HttpMethod::Patch)
            ->schema([
                Grid::make('tenants-update-fields')
                    ->columns(1)
                    ->schema([
                        TextInput::make('name', __('tenant.fields.name'))
                            ->value($tenant->name)
                            ->placeholder(__('tenant.fields.name'))
                            ->required()
                            ->rules(['string', 'max:255']),
                    ]),
            ])
            ->submitLabel(__('tenant.update.submit'));
    }

    public function handle(FormData $data): LatticeResponse
    {
        /** @var Tenant $tenant */
        $tenant = $this->contextModel('tenant');

        $oldName = $tenant->name;

        $tenant = DB::transaction(function () use ($tenant, $data): Tenant {
            $tenant = Tenant::whereKey($tenant->id)->lockForUpdate()->firstOrFail();
            $tenant->update(['name' => (string) $data['name']]);

            return $tenant;
        });

        Audit::record(TenantAuditEvent::TenantUpdated, $tenant, $tenant, [
            'name' => ['from' => $oldName, 'to' => $tenant->name],
        ]);

        return Effects::respond()->toast(__('tenant.update.updated'), Variant::Success)->toRoute('tenant.settings.edit', ['current_tenant' => $tenant->slug]);
    }
}

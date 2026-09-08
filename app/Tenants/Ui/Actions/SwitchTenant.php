<?php
declare(strict_types=1);

namespace App\Tenants\Ui\Actions;

use App\Shared\Concerns\ResolvesCurrentUser;
use App\Tenants\Models\Tenant;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Ui\Enums\Variant;

#[AsAction('tenants.switch', can: 'view', on: 'tenant')]
final class SwitchTenant extends ActionDefinition
{
    use ResolvesCurrentUser;

    public function definition(Action $action): Action
    {
        return $action->label(__('tenant.switch.label'));
    }

    public function handle(Request $request): ActionResult
    {
        /** @var Tenant $tenant */
        $tenant = $this->contextModel('tenant');

        $this->currentUser()->switchTenant($tenant);

        return ActionResult::success()
            ->toast(__('tenant.switch.switched', ['tenant' => $tenant->name]), Variant::Success)
            ->toRoute('dashboard', ['current_tenant' => $tenant->slug]);
    }
}

<?php
declare(strict_types=1);

namespace App\Realms\Ui\Actions;

use App\Realms\Models\Realm;
use App\Shared\Authorization\Enums\Scope;
use App\Shared\Realms\Contracts\ChecksRealmDomain;
use App\Shared\Realms\Enums\RealmDomainStatus;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Ui\Enums\Variant;

#[AsAction('admin.realms.domain.check', can: Scope::RealmsWrite)]
final class CheckRealmDomainAction extends ActionDefinition
{
    public function __construct(private readonly ChecksRealmDomain $checkDomain) {}

    public function definition(Action $action): Action
    {
        return $action->label(__('admin.realms.domain.check'));
    }

    #[\Override]
    public function authorize(Request $request): bool
    {
        $realm = $this->contextModelOrNull('realm', Realm::class);

        return $realm instanceof Realm && ! $realm->isMaster();
    }

    public function handle(Request $request): ActionResult
    {
        $realm = $this->contextModel('realm', Realm::class);

        $status = $this->checkDomain->handle($realm);

        return ActionResult::success()
            ->toast(
                __('admin.realms.domain.outcome.'.$status->value, ['domain' => $realm->domain]),
                $status === RealmDomainStatus::Verified ? Variant::Success : Variant::Warning,
            )
            ->reloadPage();
    }
}

<?php
declare(strict_types=1);

namespace App\Admin\Ui\Actions;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Concerns\BuildsSocialProviderFields;
use App\Admin\Ui\Concerns\ResolvesRealmSocialProvider;
use App\Admin\Ui\Tables\AdminSocialProvidersTable;
use App\Realms\Models\RealmSocialProvider;
use App\Shared\Realms\Contracts\UpdatesSocialProvider;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Form\FormData;
use Lattice\Ui\Enums\Emphasis;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsAction('admin.social-providers.update', can: [AdminPermission::Access, AdminPermission::ManageRealms])]
final class UpdateSocialProviderAction extends ActionDefinition
{
    use BuildsSocialProviderFields;
    use ResolvesRealmSocialProvider;

    public function __construct(private readonly UpdatesSocialProvider $updateProvider) {}

    public function definition(Action $action): Action
    {
        $provider = $this->socialProvider();

        return $action
            ->label(__('admin.social-providers.edit.label'))
            ->method(HttpMethod::Patch)
            ->emphasis(Emphasis::Ghost)
            ->form($this->socialProviderFields($provider));
    }

    #[\Override]
    public function authorize(Request $request): bool
    {
        return $this->socialProviderOrNull() instanceof RealmSocialProvider;
    }

    public function handle(FormData $data): ActionResult
    {
        $provider = $this->socialProvider();

        $this->updateProvider->handle(
            $provider,
            $this->submittedCredentials($provider->driver, $data),
            $data->boolean('enabled'),
        );

        return ActionResult::success()
            ->toast(__('admin.social-providers.updated'), Variant::Success)
            ->reloadComponent(AdminSocialProvidersTable::ID);
    }
}

<?php
declare(strict_types=1);

namespace App\Admin\Ui\Actions;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Concerns\BuildsSocialProviderFields;
use App\Admin\Ui\Concerns\ResolvesRealmSocialProvider;
use App\Admin\Ui\Tables\AdminSocialProvidersTable;
use App\Shared\Realms\Contracts\CreatesSocialProvider;
use App\Shared\Realms\Enums\SocialProviderDriver;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Form\FormData;
use Lattice\Ui\Enums\Variant;

#[AsAction('admin.social-providers.create', can: [AdminPermission::Access, AdminPermission::ManageRealms])]
final class CreateSocialProviderAction extends ActionDefinition
{
    use BuildsSocialProviderFields;
    use ResolvesRealmSocialProvider;

    public function __construct(private readonly CreatesSocialProvider $createProvider) {}

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('admin.social-providers.create.label'))
            ->form($this->socialProviderCreationFields($this->realm()));
    }

    public function handle(FormData $data): ActionResult
    {
        $driver = SocialProviderDriver::from((string) $data->string('driver'));

        $this->createProvider->handle(
            $this->realm(),
            trim((string) $data->string('key')),
            $driver,
            $this->submittedCredentials($driver, $data),
            $data->boolean('enabled'),
        );

        return ActionResult::success()
            ->toast(__('admin.social-providers.created'), Variant::Success)
            ->reloadComponent(AdminSocialProvidersTable::ID);
    }
}

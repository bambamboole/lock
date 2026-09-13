<?php
declare(strict_types=1);

namespace App\Realms\Ui\Actions;

use App\Realms\Actions\DeleteSocialProvider;
use App\Realms\Models\RealmSocialProvider;
use App\Realms\Ui\Concerns\ResolvesRealmSocialProvider;
use App\Shared\Authorization\Enums\Scope;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Ui\Enums\Emphasis;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsAction('admin.social-providers.delete', can: Scope::RealmsWrite)]
final class DeleteSocialProviderAction extends ActionDefinition
{
    use ResolvesRealmSocialProvider;

    public function __construct(private readonly DeleteSocialProvider $deleteProvider) {}

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('admin.social-providers.delete.label'))
            ->method(HttpMethod::Delete)
            ->variant(Variant::Danger)
            ->emphasis(Emphasis::Ghost)
            ->confirm(__('admin.social-providers.delete.confirm.title'), __('admin.social-providers.delete.confirm.description'));
    }

    #[\Override]
    public function authorize(Request $request): bool
    {
        return $this->socialProviderOrNull() instanceof RealmSocialProvider;
    }

    public function handle(Request $request): ActionResult
    {
        $realm = $this->realm();

        $this->deleteProvider->handle($this->socialProvider());

        return ActionResult::success()
            ->toast(__('admin.social-providers.deleted'), Variant::Success)
            ->toRoute('admin.realms.settings', ['realm' => $realm->slug, 'tabs' => 'social']);
    }
}

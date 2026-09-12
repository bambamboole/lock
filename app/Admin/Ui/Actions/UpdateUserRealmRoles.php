<?php
declare(strict_types=1);

namespace App\Admin\Ui\Actions;

use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Realms\Models\RealmRole;
use App\Shared\Admin\Enums\AdminPermission;
use App\Shared\Realms\Contracts\SyncsUserRealmRoles;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Form\Components\CheckboxGroup;
use Lattice\Form\FormData;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsAction('admin.users.realm-roles.update', can: [AdminPermission::Access, AdminPermission::ManageUsers])]
final class UpdateUserRealmRoles extends ActionDefinition
{
    public function __construct(private readonly SyncsUserRealmRoles $syncRoles) {}

    public function definition(Action $action): Action
    {
        $target = $this->realmUser();
        $roles = $target->realm->realmRoles()->orderBy('name')->get();

        return $action
            ->label(__('admin.users.detail.realm-roles.update'))
            ->method(HttpMethod::Patch)
            ->form([
                CheckboxGroup::make('role_ids', __('admin.users.detail.realm-roles.field.label'))
                    ->options($roles->map(fn (RealmRole $role) => CheckboxGroup::option($role->name, $role->id))->all())
                    ->value($target->realmRoles()->pluck('realm_roles.id')->all(), editable: true)
                    ->rules(['array', Rule::in($roles->modelKeys())]),
            ]);
    }

    #[\Override]
    public function authorize(Request $request): bool
    {
        return $this->realmUserOrNull() instanceof User;
    }

    public function handle(FormData $data): ActionResult
    {
        $this->syncRoles->handle($this->realmUser(), array_values(array_map(strval(...), (array) $data->get('role_ids', []))));

        return ActionResult::success()
            ->toast(__('admin.users.detail.realm-roles.updated'), Variant::Success)
            ->reloadPage();
    }

    private function realmUser(): User
    {
        $user = $this->realmUserOrNull();

        abort_unless($user instanceof User, 404);

        return $user;
    }

    private function realmUserOrNull(): ?User
    {
        $realm = $this->contextModelOrNull('realm', Realm::class);
        $user = $this->contextModelOrNull('user', User::class);

        return $realm instanceof Realm && $user instanceof User && $user->belongsToRealm($realm) ? $user : null;
    }
}

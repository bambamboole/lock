<?php

declare(strict_types=1);

namespace App\Roles\Ui\Forms;

use App\Roles\Ui\Concerns\BuildsRoleFields;
use App\Roles\Ui\Concerns\ResolvesRole;
use App\Shared\Authorization\Enums\Scope;
use App\Shared\Roles\Contracts\CreatesRole;
use Illuminate\Http\Request;
use Lattice\Facades\Effects;
use Lattice\Form\Attributes\AsForm;
use Lattice\Form\Components\Form;
use Lattice\Form\FormData;
use Lattice\Form\FormDefinition;
use Lattice\Http\LatticeResponse;
use Lattice\Ui\Enums\Variant;

#[AsForm('admin.realm-roles.create-form', can: Scope::RolesWrite)]
final class CreateRoleForm extends FormDefinition
{
    use BuildsRoleFields;
    use ResolvesRole;

    public function __construct(private readonly CreatesRole $createRole) {}

    public function definition(Form $form, Request $request): Form
    {
        return $form->schema($this->realmRoleFields($this->realm(), null))
            ->submitLabel(__('admin.roles.create.label'));
    }

    public function handle(FormData $data): LatticeResponse
    {
        $realm = $this->realm();
        $description = trim((string) $data->string('description'));
        $role = $this->createRole->handle(
            $realm,
            (string) $data->string('name'),
            $description === '' ? null : $description,
            $this->submittedScopeIds($realm, $data->get('scope_ids', [])),
        );

        return Effects::respond()->toast(__('admin.roles.created'), Variant::Success)
            ->toRoute('admin.realms.roles.show', ['realm' => $realm->slug, 'role' => $role->id]);
    }
}

<?php
declare(strict_types=1);

namespace App\Tenants\Ui\Forms;

use App\Shared\Concerns\ResolvesCurrentUser;
use App\Tenants\Actions\InviteMember;
use App\Tenants\Enums\RolePreset;
use App\Tenants\Enums\TenantPermission;
use App\Tenants\Models\Role;
use App\Tenants\Models\Tenant;
use App\Tenants\Ui\Concerns\ActivatesTenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Lattice\Facades\Effects;
use Lattice\Form\Attributes\AsForm;
use Lattice\Form\Components\Choice;
use Lattice\Form\Components\Form as FormComponent;
use Lattice\Form\Components\TextInput;
use Lattice\Form\FormData;
use Lattice\Form\FormDefinition;
use Lattice\Http\LatticeResponse;
use Lattice\Ui\Components\Grid;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsForm('tenants.invite', can: TenantPermission::ManageMembers, on: 'tenant')]
final class InviteTenantMemberForm extends FormDefinition
{
    use ActivatesTenantContext;
    use ResolvesCurrentUser;

    public function __construct(private readonly InviteMember $inviteMember) {}

    public function definition(FormComponent $form, Request $request): FormComponent
    {
        /** @var Tenant $tenant */
        $tenant = $this->contextModel('tenant');

        return $form
            ->method(HttpMethod::Post)
            ->resetOnSuccess(['email'])
            ->schema([
                Grid::make('tenants-invite-fields')
                    ->columns(1)
                    ->schema([
                        TextInput::make('email', __('common.field.email-address'))
                            ->email()
                            ->placeholder(__('tenant.invite.email.placeholder'))
                            ->required()
                            ->rules(['string', 'max:255'])
                            ->rules(fn (): array => [function (string $attribute, mixed $value, Closure $fail) use ($tenant): void {
                                if ($tenant->hasMemberOrPendingInvitation((string) $value)) {
                                    $fail(__('tenant.invite.already-member'));
                                }
                            }]),
                        Choice::make('role_id', __('common.field.role'))
                            ->value((string) $tenant->roles()->where('preset', RolePreset::Member)->value('id'))
                            ->options(
                                $tenant->assignableRoles()
                                    ->map(fn (Role $role) => Choice::option($role->displayName(), (string) $role->id))
                                    ->all(),
                            )
                            ->rules([
                                Rule::exists('roles', 'id')->where('tenant_id', $tenant->id)->whereNot('preset', 'owner'),
                            ])
                            ->required(),
                    ]),
            ])
            ->submitLabel(__('tenant.invite.submit'));
    }

    public function handle(FormData $data): LatticeResponse
    {
        /** @var Tenant $tenant */
        $tenant = $this->contextModel('tenant');

        $this->inviteMember->handle(
            $tenant,
            $this->currentUser(),
            (string) $data['email'],
            (string) $data['role_id'],
        );

        return Effects::respond()->toast(__('tenant.invite.sent'), Variant::Success)->toRoute('tenant.settings.edit', ['current_tenant' => $tenant->slug]);
    }
}

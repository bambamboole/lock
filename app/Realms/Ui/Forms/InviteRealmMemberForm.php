<?php
declare(strict_types=1);

namespace App\Realms\Ui\Forms;

use App\Realms\Actions\InviteMember;
use App\Realms\Enums\RealmPermission;
use App\Realms\Enums\RolePreset;
use App\Realms\Models\Realm;
use App\Realms\Models\Role;
use App\Realms\Ui\Concerns\ActivatesRealmContext;
use App\Shared\Concerns\ResolvesCurrentUser;
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

#[AsForm('realms.invite', can: RealmPermission::ManageMembers, on: 'realm')]
final class InviteRealmMemberForm extends FormDefinition
{
    use ActivatesRealmContext;
    use ResolvesCurrentUser;

    public function __construct(private readonly InviteMember $inviteMember) {}

    public function definition(FormComponent $form, Request $request): FormComponent
    {
        /** @var Realm $realm */
        $realm = $this->contextModel('realm');

        return $form
            ->method(HttpMethod::Post)
            ->resetOnSuccess(['email'])
            ->schema([
                Grid::make('realms-invite-fields')
                    ->columns(1)
                    ->schema([
                        TextInput::make('email', __('common.field.email-address'))
                            ->email()
                            ->placeholder(__('realm.invite.email.placeholder'))
                            ->required()
                            ->rules(['string', 'max:255'])
                            ->rules(fn (): array => [function (string $attribute, mixed $value, Closure $fail) use ($realm): void {
                                if ($realm->hasMemberOrPendingInvitation((string) $value)) {
                                    $fail(__('realm.invite.already-member'));
                                }
                            }]),
                        Choice::make('role_id', __('common.field.role'))
                            ->value((string) $realm->roles()->where('preset', RolePreset::Member)->value('id'))
                            ->options(
                                $realm->assignableRoles()
                                    ->map(fn (Role $role) => Choice::option($role->displayName(), (string) $role->id))
                                    ->all(),
                            )
                            ->rules([
                                Rule::exists('roles', 'id')->where('realm_id', $realm->id)->whereNot('preset', 'owner'),
                            ])
                            ->required(),
                    ]),
            ])
            ->submitLabel(__('realm.invite.submit'));
    }

    public function handle(FormData $data): LatticeResponse
    {
        /** @var Realm $realm */
        $realm = $this->contextModel('realm');

        $this->inviteMember->handle(
            $realm,
            $this->currentUser(),
            (string) $data['email'],
            (string) $data['role_id'],
        );

        return Effects::respond()->toast(__('realm.invite.sent'), Variant::Success)->toRoute('realm.settings.edit');
    }
}

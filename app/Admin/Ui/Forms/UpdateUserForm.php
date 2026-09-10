<?php
declare(strict_types=1);

namespace App\Admin\Ui\Forms;

use App\Admin\Actions\UpdateRealmUser;
use App\Admin\Enums\AdminPermission;
use App\Auth\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Lattice\Facades\Effects;
use Lattice\Form\Attributes\AsForm;
use Lattice\Form\Components\Form as FormComponent;
use Lattice\Form\Components\TextInput;
use Lattice\Form\Components\Toggle;
use Lattice\Form\FormData;
use Lattice\Form\FormDefinition;
use Lattice\Http\LatticeResponse;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsForm('admin.users.update', can: [AdminPermission::Access, AdminPermission::ManageUsers])]
final class UpdateUserForm extends FormDefinition
{
    public function __construct(private readonly UpdateRealmUser $updateUser) {}

    public function definition(FormComponent $form, Request $request): FormComponent
    {
        /** @var User $user */
        $user = $this->contextModel('user');

        return $form
            ->method(HttpMethod::Patch)
            ->schema([
                TextInput::make('name', __('common.field.name'))
                    ->value($user->name, editable: true)
                    ->required()
                    ->rules(['string', 'max:255']),
                TextInput::make('email', __('common.field.email-address'))
                    ->email()
                    ->value($user->email, editable: true)
                    ->required()
                    ->rules(['string', 'email', 'max:255', Rule::unique(User::class, 'email')->where('realm_id', $user->realm_id)->ignore($user->id)]),
                Toggle::make('email_verified', __('admin.users.fields.email-verified.label'))
                    ->helperText(__('admin.users.fields.email-verified.help-text'))
                    ->value($user->hasVerifiedEmail(), editable: true),
            ])
            ->submitLabel(__('admin.users.detail.profile.submit'));
    }

    public function handle(FormData $data): LatticeResponse
    {
        /** @var User $user */
        $user = $this->contextModel('user');

        $this->updateUser->handle(
            $user,
            (string) $data->string('name'),
            (string) $data->string('email'),
            $data->boolean('email_verified'),
        );

        return Effects::respond()->toast(__('admin.users.updated'), Variant::Success)
            ->toRoute('admin.realms.users.show', ['realm' => $user->realm->slug, 'user' => $user->id]);
    }
}

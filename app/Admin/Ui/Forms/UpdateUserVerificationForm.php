<?php
declare(strict_types=1);

namespace App\Admin\Ui\Forms;

use App\Shared\Admin\Enums\AdminPermission;
use Illuminate\Http\Request;
use Lattice\Form\Attributes\AsForm;
use Lattice\Form\Components\Form as FormComponent;
use Lattice\Form\Components\Toggle;
use Lattice\Form\FormData;
use Lattice\Http\LatticeResponse;

#[AsForm('admin.users.update.verification', can: [AdminPermission::Access, AdminPermission::ManageUsers])]
final class UpdateUserVerificationForm extends RealmUserForm
{
    public function definition(FormComponent $form, Request $request): FormComponent
    {
        return $this->userForm($form)->schema([
            Toggle::make('email_verified', __('admin.users.fields.email-verified.label'))
                ->helperText(__('admin.users.fields.email-verified.help-text'))
                ->value($this->user()->hasVerifiedEmail(), editable: true),
        ]);
    }

    public function handle(FormData $data): LatticeResponse
    {
        return $this->save(verified: $data->boolean('email_verified'));
    }
}

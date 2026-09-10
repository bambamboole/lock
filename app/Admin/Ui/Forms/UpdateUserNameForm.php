<?php
declare(strict_types=1);

namespace App\Admin\Ui\Forms;

use App\Admin\Enums\AdminPermission;
use Illuminate\Http\Request;
use Lattice\Form\Attributes\AsForm;
use Lattice\Form\Components\Form as FormComponent;
use Lattice\Form\Components\TextInput;
use Lattice\Form\FormData;
use Lattice\Http\LatticeResponse;

#[AsForm('admin.users.update.name', can: [AdminPermission::Access, AdminPermission::ManageUsers])]
final class UpdateUserNameForm extends RealmUserForm
{
    public function definition(FormComponent $form, Request $request): FormComponent
    {
        return $this->userForm($form)->schema([
            TextInput::make('name', __('common.field.name'))
                ->value($this->user()->name, editable: true)
                ->required()
                ->rules(['string', 'max:255']),
        ]);
    }

    public function handle(FormData $data): LatticeResponse
    {
        return $this->save(name: (string) $data->string('name'));
    }
}

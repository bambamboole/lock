<?php
declare(strict_types=1);

namespace App\Auth\Ui\Forms;

use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Lattice\Facades\Effects;
use Lattice\Form\Attributes\AsForm;
use Lattice\Form\Components\Form;
use Lattice\Form\Components\PasswordInput;
use Lattice\Form\FormData;
use Lattice\Http\LatticeResponse;
use Lattice\Ui\Components\Grid;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsForm('profile.password')]
final class PasswordForm extends ProfileForm
{
    public function definition(Form $form, Request $request): Form
    {
        return $this->profileForm($form)
            ->method(HttpMethod::Put)
            ->schema([
                Grid::make('password-fields')
                    ->columns(1)
                    ->schema([
                        PasswordInput::make('current_password', __('user.security.password.current'))
                            ->autoComplete('current-password')
                            ->placeholder(__('user.security.password.current'))
                            ->required()
                            ->rules(['current_password']),
                        PasswordInput::make('password', __('user.security.password.new'))
                            ->autoComplete('new-password')
                            ->placeholder(__('user.security.password.new'))
                            ->passwordRules(Password::default()->toPasswordRulesString())
                            ->needsConfirmation()
                            ->required()
                            ->rules(['string', 'confirmed', Password::default()]),
                    ]),
            ])
            ->resetOnError(['password', 'password_confirmation', 'current_password'])
            ->resetOnSuccess();
    }

    public function handle(FormData $data): LatticeResponse
    {
        $user = $this->currentUser();

        $user->update([
            'password' => $data['password'],
        ]);

        return Effects::respond()->toast(__('user.security.password.updated'), Variant::Success)->back();
    }
}

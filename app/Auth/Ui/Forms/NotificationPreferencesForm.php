<?php
declare(strict_types=1);

namespace App\Auth\Ui\Forms;

use Illuminate\Http\Request;
use Lattice\Form\Attributes\AsForm;
use Lattice\Form\Components\Checkbox;
use Lattice\Form\Components\Form;
use Lattice\Form\FormData;
use Lattice\Http\LatticeResponse;

#[AsForm('profile.preferences.notifications')]
final class NotificationPreferencesForm extends ProfileForm
{
    public function definition(Form $form, Request $request): Form
    {
        $user = $this->currentUser();

        return $this->profileForm($form)
            ->fill([
                'realm-invitations' => $user->settings->boolean('notifications.realm-invitations', true),
                'realm-membership' => $user->settings->boolean('notifications.realm-membership', true),
            ])
            ->schema([
                Checkbox::make('realm-invitations', __('user.preferences.notifications.realm-invitations.label'))
                    ->helperText(__('user.preferences.notifications.realm-invitations.help-text')),
                Checkbox::make('realm-membership', __('user.preferences.notifications.realm-membership.label'))
                    ->helperText(__('user.preferences.notifications.realm-membership.help-text')),
            ]);
    }

    public function handle(FormData $data): LatticeResponse
    {
        $user = $this->currentUser();

        $user->settings->set('notifications.realm-invitations', $data->boolean('realm-invitations'));
        $user->settings->set('notifications.realm-membership', $data->boolean('realm-membership'));

        return $this->saved(__('user.preferences.updated'));
    }
}

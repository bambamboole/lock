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
                'tenant-invitations' => $user->settings->boolean('notifications.tenant-invitations', true),
                'tenant-membership' => $user->settings->boolean('notifications.tenant-membership', true),
            ])
            ->schema([
                Checkbox::make('tenant-invitations', __('user.preferences.notifications.tenant-invitations.label'))
                    ->helperText(__('user.preferences.notifications.tenant-invitations.help-text')),
                Checkbox::make('tenant-membership', __('user.preferences.notifications.tenant-membership.label'))
                    ->helperText(__('user.preferences.notifications.tenant-membership.help-text')),
            ]);
    }

    public function handle(FormData $data): LatticeResponse
    {
        $user = $this->currentUser();

        $user->settings->set('notifications.tenant-invitations', $data->boolean('tenant-invitations'));
        $user->settings->set('notifications.tenant-membership', $data->boolean('tenant-membership'));

        return $this->saved(__('user.preferences.updated'));
    }
}

<?php
declare(strict_types=1);

use App\Auth\Models\User;
use App\Auth\Ui\Forms\NotificationPreferencesForm;

it('saves an unchecked notification preference as false', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->submitForm(NotificationPreferencesForm::class, [
            'tenant-membership' => true,
        ])
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->settings->get('notifications.tenant-invitations'))->toBeFalse()
        ->and($user->settings->get('notifications.tenant-membership'))->toBeTrue();
});

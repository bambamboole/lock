<?php
declare(strict_types=1);

use App\Auth\Models\User;
use App\Auth\Ui\Actions\DeleteUserAccount;

test('a user can delete their account with the correct password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->callAction(DeleteUserAccount::class, ['password' => 'password']);

    expect(User::find($user->id))->toBeNull();
});

test('deleting the account requires the correct password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->callAction(DeleteUserAccount::class, ['password' => 'wrong-password']);

    expect(User::find($user->id))->not->toBeNull();
});

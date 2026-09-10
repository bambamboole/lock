<?php
declare(strict_types=1);

use App\Auth\Models\User;
use App\Auth\Ui\Forms\LanguageForm;
use App\Auth\Ui\Forms\TimezoneForm;

it('saves the locale via the language form', function () {
    $user = User::factory()->create(['locale' => 'en']);

    $this->actingAs($user)
        ->submitForm(LanguageForm::class, ['locale' => 'de'])
        ->assertRedirect(route('account', ['realm' => $user->realm->slug]));

    expect($user->refresh()->locale)->toBe('de');
});

it('saves the timezone via the timezone form', function () {
    $user = User::factory()->create(['timezone' => null]);

    $this->actingAs($user)
        ->submitForm(TimezoneForm::class, ['timezone' => 'Europe/Berlin'])
        ->assertRedirect(route('account', ['realm' => $user->realm->slug]));

    expect($user->refresh()->timezone)->toBe('Europe/Berlin');
});

it('rejects an unknown locale', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->submitForm(LanguageForm::class, ['locale' => 'xx'])
        ->assertSessionHasErrors('locale');
});

it('rejects an invalid timezone', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->submitForm(TimezoneForm::class, ['timezone' => 'Not/AZone'])
        ->assertSessionHasErrors('timezone');
});

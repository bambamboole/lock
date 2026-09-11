<?php

declare(strict_types=1);

use App\Auth\Ui\Pages\DevLoginPage;
use Bambamboole\LaravelOidc\Server\Authentication\Views\LoginView;

use function Tests\Helpers\latticeSchema;

it('pre-fills the seeded dev credentials on the login form when DevLoginPage is bound', function () {
    $this->app->bind(LoginView::class, DevLoginPage::class);

    $root = latticeSchema($this->get(route('identity.login'))->assertOk());

    expect($root->fieldOrFail('email')->prop('value'))->toBe('test@example.com')
        ->and($root->fieldOrFail('password')->prop('value'))->toBe('password');
});

it('leaves LoginView on the upstream page outside the local environment', function () {
    expect($this->app->make(LoginView::class))->not->toBeInstanceOf(DevLoginPage::class);
});

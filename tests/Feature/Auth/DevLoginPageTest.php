<?php

declare(strict_types=1);

use App\Auth\Ui\Pages\DevLoginPage;
use Bambamboole\LaravelOidc\Server\Authentication\Views\LoginView;

it('leaves LoginView on the upstream page outside the local environment', function () {
    expect($this->app->make(LoginView::class))->not->toBeInstanceOf(DevLoginPage::class);
});

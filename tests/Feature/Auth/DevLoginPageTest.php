<?php

declare(strict_types=1);

use App\Auth\Ui\Pages\DevLoginPage;
use Lock\Server\Authentication\Ui\Views\LoginView;

it('leaves LoginView on the upstream page outside the local environment', function () {
    expect($this->app->make(LoginView::class))->not->toBeInstanceOf(DevLoginPage::class);
});

<?php

declare(strict_types=1);

namespace App\Auth\Ui\Pages;

use Bambamboole\LaravelOidc\Ui\Pages\LoginPage;
use Lattice\Form\Components\PasswordInput;
use Lattice\Form\Components\TextInput;

final class DevLoginPage extends LoginPage
{
    #[\Override]
    protected function emailField(): TextInput
    {
        return parent::emailField()->value('test@example.com');
    }

    #[\Override]
    protected function passwordInput(): PasswordInput
    {
        return parent::passwordInput()->value('password');
    }
}

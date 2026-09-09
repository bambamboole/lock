<?php

declare(strict_types=1);

namespace App\Auth\Ui\Pages;

use Bambamboole\LaravelOidc\Server\Authentication\Views\LoginPrompt;
use Bambamboole\LaravelOidc\Ui\Pages\LoginPage;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Lattice\Form\Components\PasswordInput;
use Lattice\Form\Components\TextInput;
use Symfony\Component\HttpFoundation\Response;

final class DevLoginPage extends LoginPage
{
    /**
     * LoginPage::respond() hardcodes `new self(...)`, which discards this subclass.
     * Reinstantiating here with `self` (now resolving to DevLoginPage) works around it
     * until upstream switches to `new static(...)`.
     */
    #[\Override]
    public function respond(LoginPrompt $prompt, Request $request): Responsable|Response
    {
        return new self($prompt)->toResponse($request);
    }

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

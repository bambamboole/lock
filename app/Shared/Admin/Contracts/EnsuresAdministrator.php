<?php
declare(strict_types=1);

namespace App\Shared\Admin\Contracts;

use App\Shared\Console\Enums\BootstrapOutcome;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

interface EnsuresAdministrator
{
    /**
     * Ensures a verified master-realm user with that address holds the Super
     * Admin role. A password is applied on creation and on every later call;
     * null leaves an existing password alone and invites a new user instead.
     *
     * @throws ValidationException when the password violates the master realm's policy
     */
    public function handle(string $email, string $name, #[SensitiveParameter] ?string $password): BootstrapOutcome;
}

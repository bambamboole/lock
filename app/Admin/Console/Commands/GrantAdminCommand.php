<?php

declare(strict_types=1);

namespace App\Admin\Console\Commands;

use App\Admin\Actions\GrantSuperAdmin;
use App\Realms\Models\Realm;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

#[Description('Grant a user the Super Admin global role.')]
#[Signature('admin:grant {email : Email of the user to promote}')]
final class GrantAdminCommand extends Command
{
    public function handle(GrantSuperAdmin $grant): int
    {
        $emailArgument = $this->argument('email');
        $email = is_string($emailArgument) ? $emailArgument : '';

        // An address is only unique within a realm, and instance admins live
        // in the master realm — so that is the only place worth looking.
        $user = Realm::master()->users()->where('email', $email)->first();

        if ($user === null) {
            $this->components->error("No user found for [{$email}] in the master realm.");

            return self::FAILURE;
        }

        try {
            $grant->handle($user, 'console:admin:grant');
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Granted Super Admin to {$user->email}.");

        return self::SUCCESS;
    }
}

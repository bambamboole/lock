<?php

declare(strict_types=1);

namespace App\Admin\Console\Commands;

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Admin\Enums\AdminAuditEvent;
use App\Admin\Models\Role;
use App\Realms\Models\Realm;
use App\Shared\Audit\Audit;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Grant a user the Super Admin global role.')]
#[Signature('admin:grant {email : Email of the user to promote}')]
final class GrantAdminCommand extends Command
{
    public function handle(): int
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

        $role = Role::query()->where('name', GlobalRoleSeeder::SUPER_ADMIN)->first();

        if ($role === null) {
            $this->components->error('The Super Admin role is not seeded. Run db:seed or app install first.');

            return self::FAILURE;
        }

        $alreadyGranted = $user->roles()->where('roles.id', $role->id)->exists();

        $user->roles()->syncWithoutDetaching([$role->id]);

        if (! $alreadyGranted) {
            Audit::record(
                AdminAuditEvent::SuperAdminGranted,
                $user,
                properties: ['actor' => 'console:admin:grant', 'added' => [$role->name], 'removed' => []],
            );
        }

        $this->components->info("Granted Super Admin to {$user->email}.");

        return self::SUCCESS;
    }
}

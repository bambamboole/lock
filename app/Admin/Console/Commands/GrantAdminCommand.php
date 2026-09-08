<?php

declare(strict_types=1);

namespace App\Admin\Console\Commands;

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Admin\Enums\AdminAuditEvent;
use App\Auth\Models\User;
use App\Tenants\Models\Role;
use App\Tenants\Support\Audit;
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

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->components->error("No user found for [{$email}].");

            return self::FAILURE;
        }

        $role = Role::query()->whereNull('tenant_id')->where('name', GlobalRoleSeeder::SUPER_ADMIN)->first();

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
                properties: ['actor' => 'console:admin:grant', 'added' => [$role->displayName()], 'removed' => []],
            );
        }

        $this->components->info("Granted Super Admin to {$user->email}.");

        return self::SUCCESS;
    }
}

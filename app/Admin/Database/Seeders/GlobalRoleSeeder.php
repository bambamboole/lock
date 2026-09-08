<?php

declare(strict_types=1);

namespace App\Admin\Database\Seeders;

use App\Admin\Support\GlobalRoles;
use App\Realms\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class GlobalRoleSeeder extends Seeder
{
    public const string SUPER_ADMIN = GlobalRoles::SUPER_ADMIN;

    public const string SUPPORT = GlobalRoles::SUPPORT;

    /**
     * firstOrCreate, not sole(): on a freshly provisioned environment the
     * deploy runs before anything has seeded the protected roles.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (GlobalRoles::grants() as $name => $permissions) {
                Role::query()
                    ->firstOrCreate(['realm_id' => null, 'name' => $name])
                    ->syncPermissions($permissions);
            }
        });
    }
}

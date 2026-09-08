<?php

declare(strict_types=1);

namespace App\Admin\Database\Seeders;

use App\Admin\Support\GlobalRoles;
use App\Tenants\Models\Role;
use Illuminate\Database\Seeder;

final class GlobalRoleSeeder extends Seeder
{
    public const string SUPER_ADMIN = GlobalRoles::SUPER_ADMIN;

    public const string SUPPORT = GlobalRoles::SUPPORT;

    public function run(): void
    {
        foreach (GlobalRoles::grants() as $name => $permissions) {
            Role::query()
                ->firstOrCreate(['tenant_id' => null, 'name' => $name])
                ->syncPermissions($permissions);
        }
    }
}

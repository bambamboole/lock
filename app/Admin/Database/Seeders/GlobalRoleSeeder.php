<?php

declare(strict_types=1);

namespace App\Admin\Database\Seeders;

use App\Admin\Models\Role;
use App\Admin\Support\GlobalRoles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class GlobalRoleSeeder extends Seeder
{
    public const string SUPER_ADMIN = GlobalRoles::SUPER_ADMIN;

    public const string SUPPORT = GlobalRoles::SUPPORT;

    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (GlobalRoles::grants() as $name => $permissions) {
                Role::query()
                    ->firstOrCreate(['name' => $name])
                    ->syncPermissions($permissions);
            }
        });
    }
}

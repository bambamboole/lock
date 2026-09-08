<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Tenants\Models\Role;
use App\Tenants\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
final class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->unique()->jobTitle(),
            'preset' => null,
        ];
    }

    public function global(): static
    {
        return $this->state(['tenant_id' => null]);
    }
}

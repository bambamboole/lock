<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Realms\Models\Realm;
use App\Realms\Models\Role;
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
            'realm_id' => Realm::factory(),
            'name' => fake()->unique()->jobTitle(),
            'preset' => null,
        ];
    }

    public function global(): static
    {
        return $this->state(['realm_id' => null]);
    }
}

<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Realms\Models\Realm;
use App\Realms\Models\RealmRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RealmRole>
 */
final class RealmRoleFactory extends Factory
{
    /** @var class-string<RealmRole> */
    protected $model = RealmRole::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'realm_id' => Realm::factory(),
            'name' => Str::slug(fake()->unique()->jobTitle()),
            'description' => fake()->optional()->sentence(),
        ];
    }
}

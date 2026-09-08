<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Realms\Models\Realm;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Realm>
 */
final class RealmFactory extends Factory
{
    /** @var class-string<Realm> */
    protected $model = Realm::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}

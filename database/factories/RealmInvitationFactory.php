<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Realms\Models\RealmInvitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RealmInvitation>
 */
final class RealmInvitationFactory extends Factory
{
    /** @var class-string<RealmInvitation> */
    protected $model = RealmInvitation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'realm_id' => Realm::factory(),
            'email' => fake()->unique()->safeEmail(),
            'role_id' => fn (array $attributes) => Realm::query()
                ->whereKey($attributes['realm_id'])
                ->first()
                ?->roles()->where('preset', 'member')->value('id'),
            'invited_by' => User::factory(),
            'expires_at' => null,
            'accepted_at' => null,
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'accepted_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function expiresIn(int $value, string $unit = 'days'): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->add($unit, $value),
        ]);
    }
}

<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Auth\Models\User;
use App\Tenants\Models\Tenant;
use App\Tenants\Models\TenantInvitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantInvitation>
 */
final class TenantInvitationFactory extends Factory
{
    /** @var class-string<TenantInvitation> */
    protected $model = TenantInvitation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'email' => fake()->unique()->safeEmail(),
            'role_id' => fn (array $attributes) => Tenant::query()
                ->whereKey($attributes['tenant_id'])
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

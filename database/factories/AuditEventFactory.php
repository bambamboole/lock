<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Realms\Models\Realm;
use App\Shared\Audit\Enums\AuditEventType;
use App\Shared\Audit\Models\AuditEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditEvent>
 */
final class AuditEventFactory extends Factory
{
    /** @var class-string<AuditEvent> */
    protected $model = AuditEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(AuditEventType::cases());

        return [
            'realm_id' => Realm::factory(),
            'type' => $type->value,
            'category' => $type->category()->value,
            'user_id' => null,
            'client_id' => null,
            'sid' => null,
            'ip' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'failure' => $type->isFailure(),
            'context' => null,
            'occurred_at' => now(),
        ];
    }

    public function ofType(AuditEventType $type): self
    {
        return $this->state([
            'type' => $type->value,
            'category' => $type->category()->value,
            'failure' => $type->isFailure(),
        ]);
    }
}

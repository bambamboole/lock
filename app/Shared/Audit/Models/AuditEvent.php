<?php

declare(strict_types=1);

namespace App\Shared\Audit\Models;

use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Shared\Audit\Enums\AuditEventType;
use App\Shared\Audit\Support\DatabaseAuditSink;
use Carbon\CarbonImmutable;
use Database\Factories\AuditEventFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A security-relevant event recorded by {@see DatabaseAuditSink}.
 * Separate from `activity_log`: these rows carry request provenance (ip, user
 * agent, session), survive the deletion of everything they point at, and
 * arrive at a volume the admin trail never reaches.
 *
 * @property string $id
 * @property string|null $realm_id
 * @property string $type
 * @property string $category
 * @property string|null $user_id
 * @property string|null $client_id
 * @property string|null $sid
 * @property string|null $ip
 * @property string|null $user_agent
 * @property bool $failure
 * @property array<string, mixed>|null $context
 * @property CarbonImmutable $occurred_at
 * @property-read User|null $user
 * @property-read string $actor_name
 * @property-read string $type_label
 *
 * @method static AuditEventFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditEvent query()
 *
 * @mixin \Eloquent
 */
#[Appends(['actor_name', 'type_label'])]
#[Fillable([
    'realm_id',
    'type',
    'category',
    'user_id',
    'client_id',
    'sid',
    'ip',
    'user_agent',
    'failure',
    'context',
    'occurred_at',
])]
#[WithoutTimestamps]
final class AuditEvent extends Model
{
    /** @use HasFactory<AuditEventFactory> */
    use HasFactory, HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'failure' => 'boolean',
            'context' => 'array',
            'occurred_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Realm, $this>
     */
    public function realm(): BelongsTo
    {
        return $this->belongsTo(Realm::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<AuditEvent>  $query
     * @return Builder<AuditEvent>
     */
    #[Scope]
    protected function forRealm(Builder $query, Realm $realm): Builder
    {
        return $query->where('realm_id', $realm->id);
    }

    /**
     * A type this app does not know — a case a package update added — still
     * has to list, so it renders as its own raw code.
     *
     * @return Attribute<string, never>
     */
    protected function typeLabel(): Attribute
    {
        return Attribute::make(get: fn (): string => AuditEventType::tryFrom($this->type)?->label() ?? $this->type);
    }

    /**
     * A failed login has no user to name, only the address that was tried —
     * and a deleted user leaves its rows behind on purpose.
     *
     * @return Attribute<string, never>
     */
    protected function actorName(): Attribute
    {
        return Attribute::make(get: function (): string {
            $user = $this->user;

            if ($user instanceof User) {
                return $user->name;
            }

            $username = $this->context['username'] ?? null;

            return is_string($username) && $username !== '' ? $username : '—';
        });
    }
}

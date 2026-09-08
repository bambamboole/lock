<?php
declare(strict_types=1);

namespace App\Tenants\Support;

use App\Shared\Contracts\AuditEvent;
use App\Tenants\Models\Activity;
use App\Tenants\Models\Tenant;
use Illuminate\Database\Eloquent\Model;

final class Audit
{
    /**
     * The description column stores the event's translation key, not resolved
     * text — a row written by a German admin must still render in the
     * viewer's locale. Activity::description() resolves it at read time.
     *
     * @param  array<string, mixed>  $properties
     */
    public static function record(AuditEvent $event, Model $subject, ?Tenant $tenant = null, array $properties = []): void
    {
        activity($tenant instanceof Tenant ? 'tenant' : 'admin')
            ->event($event->eventName())
            ->performedOn($subject)
            ->withProperties($properties)
            ->tap(fn (Activity $activity) => $activity->tenant_id = $tenant?->id)
            ->log($event->translationKey());
    }
}

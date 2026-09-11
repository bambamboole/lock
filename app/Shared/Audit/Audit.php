<?php
declare(strict_types=1);

namespace App\Shared\Audit;

use App\Realms\Models\Realm;
use App\Shared\Audit\Contracts\AuditEvent;
use App\Shared\Audit\Models\Activity;
use Illuminate\Database\Eloquent\Model;

final class Audit
{
    /**
     * The description stores the event's translation key, so a row renders in
     * the viewer's locale rather than the writer's.
     *
     * @param  array<string, mixed>  $properties
     */
    public static function record(AuditEvent $event, Model $subject, ?Realm $realm = null, array $properties = []): void
    {
        activity($realm instanceof Realm ? 'realm' : 'admin')
            ->event($event->eventName())
            ->performedOn($subject)
            ->withProperties($properties)
            ->tap(fn (Activity $activity) => $activity->realm_id = $realm?->id)
            ->log($event->translationKey());
    }
}

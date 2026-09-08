<?php
declare(strict_types=1);

namespace App\Realms\Support;

use App\Realms\Models\Activity;
use App\Realms\Models\Realm;
use App\Shared\Contracts\AuditEvent;
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

<?php
declare(strict_types=1);

namespace App\Shared\Concerns;

use App\Realms\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

trait RecordsActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('realm')
            ->logAll()
            ->logExcept(['id', 'realm_id', 'created_at', 'updated_at', 'deleted_at', ...$this->activityLogExcept()])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $event): string => "audit.events.model.{$event}");
    }

    /**
     * The realm column is not part of the diff, but the audit trail is read per
     * realm — stamp it so a realm's log never leaks another realm's rows.
     */
    public function beforeActivityLogged(Activity $activity, string $event): void
    {
        $activity->realm_id = $this->getAttribute('realm_id');
    }

    /**
     * Additional attributes a model keeps out of its audit trail — blob/full-text
     * payloads and derived counters, never business fields.
     *
     * @return list<string>
     */
    protected function activityLogExcept(): array
    {
        return [];
    }
}

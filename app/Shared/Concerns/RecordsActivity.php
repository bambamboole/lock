<?php
declare(strict_types=1);

namespace App\Shared\Concerns;

use App\Tenants\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

trait RecordsActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('tenant')
            ->logAll()
            ->logExcept(['id', 'tenant_id', 'created_at', 'updated_at', 'deleted_at', ...$this->activityLogExcept()])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $event): string => "audit.events.model.{$event}");
    }

    /**
     * The tenant column is not part of the diff, but the audit trail is read per
     * tenant — stamp it so a tenant's log never leaks another tenant's rows.
     */
    public function beforeActivityLogged(Activity $activity, string $event): void
    {
        $activity->tenant_id = $this->getAttribute('tenant_id');
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

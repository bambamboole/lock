<?php

declare(strict_types=1);

namespace App\Realms\Ui\Tables;

use App\Realms\Enums\RealmAuditEvent;
use App\Realms\Enums\RealmPermission;
use App\Realms\Models\Realm;
use App\Realms\Ui\Concerns\ActivatesRealmContext;
use App\Realms\Ui\Fragments\ActivityChangesFragment;
use App\Shared\Audit\Models\Activity;
use App\Shared\Ui\Tables\ActivityTable;
use Illuminate\Database\Eloquent\Builder;
use Lattice\Fragments\Components\Fragment;
use Lattice\Table\Attributes\AsTable;

#[AsTable('realm.settings.activity', can: RealmPermission::ViewAudit, on: 'realm')]
final class RealmActivityTable extends ActivityTable
{
    use ActivatesRealmContext;

    /**
     * @return Builder<Activity>
     */
    protected function scopedActivity(): Builder
    {
        /** @var Realm $realm */
        $realm = $this->contextModel('realm');

        return Activity::query()->forRealm($realm);
    }

    protected function langPrefix(): string
    {
        return 'realm.settings.activity';
    }

    protected function eventOptions(): array
    {
        $options = [];

        foreach (['created', 'updated', 'deleted'] as $lifecycle) {
            $options[$lifecycle] = __("audit.events.model.{$lifecycle}");
        }

        foreach (RealmAuditEvent::cases() as $event) {
            $options[$event->value] = __($event->translationKey());
        }

        return $options;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function changesFragment(array $row): Fragment
    {
        return Fragment::lazy(ActivityChangesFragment::class, [
            'activity' => $row['id'],
        ]);
    }
}

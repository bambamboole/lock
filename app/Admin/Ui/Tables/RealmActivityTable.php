<?php

declare(strict_types=1);

namespace App\Admin\Ui\Tables;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Fragments\RealmActivityChangesFragment;
use App\Realms\Models\Realm;
use App\Shared\Audit\Models\Activity;
use App\Shared\Realms\Enums\RealmAuditEvent;
use App\Shared\Resources\Enums\ResourceAuditEvent;
use App\Shared\Ui\Tables\ActivityTable;
use Illuminate\Database\Eloquent\Builder;
use Lattice\Fragments\Components\Fragment;
use Lattice\Table\Attributes\AsTable;

#[AsTable('admin.realm-activity', can: [AdminPermission::Access, AdminPermission::ViewRealms])]
final class RealmActivityTable extends ActivityTable
{
    /**
     * @return Builder<Activity>
     */
    protected function scopedActivity(): Builder
    {
        $realm = $this->contextModel('realm');

        abort_unless($realm instanceof Realm, 404);

        return Activity::forRealm($realm);
    }

    protected function langPrefix(): string
    {
        return 'admin.realm-activity';
    }

    protected function eventOptions(): array
    {
        $options = [];

        foreach ([...RealmAuditEvent::cases(), ...ResourceAuditEvent::cases()] as $event) {
            $options[$event->value] = __($event->translationKey());
        }

        return $options;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function changesFragment(array $row): Fragment
    {
        return Fragment::lazy(RealmActivityChangesFragment::class, ['activity' => $row['id']]);
    }
}

<?php
declare(strict_types=1);

namespace App\Tenants\Actions;

use App\Auth\Models\User;
use App\Tenants\Enums\TenantAuditEvent;
use App\Tenants\Models\Tenant;
use App\Tenants\Support\Audit;
use Illuminate\Support\Facades\DB;
use Lattice\Notifications\Notification;
use Lattice\Ui\Enums\Variant;

final class RemoveMember
{
    public function handle(Tenant $tenant, User $member): void
    {
        abort_if($tenant->owner()?->is($member) === true, 403, __('tenant.members.owner-cannot-be-removed'));

        DB::transaction(function () use ($tenant, $member): void {
            $tenant->memberships()->where('user_id', $member->id)->delete();

            $member->roles()->detach($tenant->roles()->pluck('id'));
        });

        $this->notifyRemovedMember($member, $tenant);

        Audit::record(TenantAuditEvent::MemberRemoved, $member, $tenant, [
            'member' => $member->name,
        ]);

        if ($member->isCurrentTenant($tenant)) {
            $personalTenant = $member->personalTenant();

            if ($personalTenant instanceof Tenant) {
                $member->switchTenant($personalTenant);
            }
        }
    }

    private function notifyRemovedMember(User $member, Tenant $tenant): void
    {
        if (! $member->settings->boolean('notifications.tenant-membership', true)) {
            return;
        }

        Notification::make()
            ->title(rt('tenant:notification.removed.title'))
            ->body(rt('tenant:notification.removed.body')->with(['tenant' => $tenant->name]))
            ->icon('users')
            ->variant(Variant::Warning)
            ->send($member);
    }
}

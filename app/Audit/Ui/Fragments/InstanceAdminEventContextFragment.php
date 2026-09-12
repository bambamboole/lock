<?php
declare(strict_types=1);

namespace App\Audit\Ui\Fragments;

use App\Audit\Models\AdminEvent;
use App\Shared\Authorization\Enums\Scope;
use Lattice\Core\Attributes\AsFragment;

#[AsFragment('admin.admin-event-context', can: Scope::AdminEventsRead)]
final class InstanceAdminEventContextFragment extends AdminEventContextFragment
{
    protected function adminEvent(): ?AdminEvent
    {
        return AdminEvent::global()->find($this->contextString('adminEvent'));
    }
}

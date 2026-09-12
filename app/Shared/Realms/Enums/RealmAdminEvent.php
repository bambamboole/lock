<?php
declare(strict_types=1);

namespace App\Shared\Realms\Enums;

use App\Shared\Audit\Concerns\IsAdminEventType;
use App\Shared\Audit\Contracts\AdminEventType;

enum RealmAdminEvent: string implements AdminEventType
{
    use IsAdminEventType;

    case RealmCreated = 'realm.created';
    case RealmUpdated = 'realm.updated';
    case RealmDeleted = 'realm.deleted';
}

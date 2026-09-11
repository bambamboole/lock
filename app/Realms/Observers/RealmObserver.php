<?php
declare(strict_types=1);

namespace App\Realms\Observers;

use App\Realms\Models\Realm;
use App\Shared\Realms\Support\RealmConfiguration;
use LogicException;

final class RealmObserver
{
    public function updating(Realm $realm): void
    {
        if ($realm->isDirty('slug')) {
            throw new LogicException('Realm identifiers cannot be changed.');
        }
    }

    public function deleting(Realm $realm): void
    {
        if ($realm->isMaster()) {
            throw new LogicException('The administration realm cannot be deleted.');
        }
    }

    public function created(Realm $realm): void
    {
        foreach (RealmConfiguration::defaults() as $key => $value) {
            $realm->storedSettings()->create(['key' => 'realm.'.$key, 'value' => $value]);
        }
    }
}

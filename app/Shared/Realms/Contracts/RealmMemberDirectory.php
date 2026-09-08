<?php

declare(strict_types=1);

namespace App\Shared\Realms\Contracts;

use App\Realms\Models\Realm;
use Illuminate\Support\Collection;

interface RealmMemberDirectory
{
    /**
     * Members with their role labels, owner first and then alphabetically by name.
     *
     * @return Collection<int, array{id: string, name: string, email: string, role_labels: string, is_owner: bool}>
     */
    public function rows(Realm $realm, string $search = ''): Collection;
}

<?php

declare(strict_types=1);

namespace App\Admin\Ui\Pages;

use App\Admin\Enums\AdminPermission;
use App\Realms\Models\Realm;
use Lattice\Core\Attributes\AsPage;
use Lattice\Core\Breadcrumb;
use Lattice\Http\Page;

#[AsPage(layout: 'admin', middleware: ['web', 'auth', 'verified'], can: AdminPermission::Access)]
abstract class AdminPage extends Page
{
    /**
     * @return array<int, Breadcrumb>
     */
    protected function realmBreadcrumbs(Realm $realm, Breadcrumb ...$trail): array
    {
        return [Breadcrumb::make($realm->name, route('admin.realms.users', ['realm' => $realm->slug], false)), ...array_values($trail)];
    }
}

<?php

declare(strict_types=1);

namespace App\Admin\Ui\Pages;

use App\Admin\Enums\AdminPermission;
use Lattice\Core\Attributes\AsPage;
use Lattice\Http\Page;

#[AsPage(layout: 'admin', middleware: ['web', 'auth', 'verified'], can: AdminPermission::Access)]
abstract class AdminPage extends Page {}

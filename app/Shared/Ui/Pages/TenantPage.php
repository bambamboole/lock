<?php

declare(strict_types=1);

namespace App\Shared\Ui\Pages;

use App\Tenants\Http\Middleware\SwitchesCurrentTenant;
use Lattice\Core\Attributes\AsPage;
use Lattice\Core\Enums\PageLayout;
use Lattice\Http\Page;

#[AsPage(
    layout: PageLayout::App,
    middleware: ['web', 'auth', 'verified', SwitchesCurrentTenant::class],
    can: 'view',
    on: 'current_tenant',
)]
abstract class TenantPage extends Page {}

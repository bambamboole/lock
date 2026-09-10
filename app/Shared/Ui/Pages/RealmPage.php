<?php

declare(strict_types=1);

namespace App\Shared\Ui\Pages;

use Lattice\Core\Attributes\AsPage;
use Lattice\Core\Enums\PageLayout;
use Lattice\Http\Page;

#[AsPage(
    layout: PageLayout::App,
    middleware: ['web', 'auth', 'verified'],
)]
abstract class RealmPage extends Page {}

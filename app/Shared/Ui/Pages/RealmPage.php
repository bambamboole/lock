<?php

declare(strict_types=1);

namespace App\Shared\Ui\Pages;

use Lattice\Core\Attributes\AsPage;
use Lattice\Core\Enums\PageLayout;
use Lattice\Http\Page;

/**
 * A page rendered inside the signed-in user's own realm. There is no realm in
 * the URL to authorize against — a user has exactly one realm — so membership
 * is structural and the per-tab permission checks carry the rest.
 */
#[AsPage(
    layout: PageLayout::App,
    middleware: ['web', 'auth', 'verified'],
)]
abstract class RealmPage extends Page {}

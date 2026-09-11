<?php

declare(strict_types=1);

namespace App\Auth\Ui\Pages;

use Bambamboole\LaravelOidc\Ui\Pages\SetupTwoFactorPage as PackageSetupTwoFactorPage;
use Lattice\Core\Attributes\AsPage;

/**
 * laravel-oidc renders this page from its own controller, so the account area
 * is chosen on the page rather than on a route: its setup form posts to the
 * identity-guarded endpoints like every component on the account page.
 */
#[AsPage(endpoints: 'account')]
final class SetupTwoFactorPage extends PackageSetupTwoFactorPage {}

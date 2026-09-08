<?php
declare(strict_types=1);

namespace App\Auth\Models;

use Laravel\Passport\Token;

/**
 * The Passport token model laravel-oidc issues access tokens through
 * (config/oidc.php `passport.token_model`). Kept as an app-owned class so
 * OIDC-specific columns and behaviour have a home.
 */
final class OAuthAccessToken extends Token {}

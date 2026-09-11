<?php
declare(strict_types=1);

namespace App\Shared\Resources\Contracts;

use App\Realms\Models\Realm;
use Bambamboole\LaravelOidc\Server\Shared\Realms\Settings\ResourceSettings;

/**
 * The realm's resource servers as laravel-oidc reads them: the ownership source
 * for scope issuance and the RFC 9728 advertisement alike. Resources owns the
 * rows; App\Realms\Models\Realm reaches them through this contract.
 */
interface ProvidesRealmResources
{
    public function for(Realm $realm): ResourceSettings;
}

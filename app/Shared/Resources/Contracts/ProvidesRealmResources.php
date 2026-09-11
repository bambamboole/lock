<?php
declare(strict_types=1);

namespace App\Shared\Resources\Contracts;

use App\Realms\Models\Realm;
use Bambamboole\LaravelOidc\Server\Shared\Realms\Settings\ResourceSettings;

interface ProvidesRealmResources
{
    public function for(Realm $realm): ResourceSettings;
}

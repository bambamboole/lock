<?php
declare(strict_types=1);

namespace App\Shared\Realms\Contracts;

use App\Realms\Models\Realm;
use App\Realms\Models\RealmSocialProvider;
use App\Shared\Realms\Enums\SocialProviderDriver;

interface CreatesSocialProvider
{
    /** @param array<string, string> $config */
    public function handle(Realm $realm, string $key, SocialProviderDriver $driver, array $config, bool $enabled): RealmSocialProvider;
}

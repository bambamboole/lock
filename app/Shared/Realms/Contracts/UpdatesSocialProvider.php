<?php
declare(strict_types=1);

namespace App\Shared\Realms\Contracts;

use App\Realms\Models\RealmSocialProvider;

interface UpdatesSocialProvider
{
    /**
     * A secret left blank keeps the stored one.
     *
     * @param  array<string, string>  $config
     */
    public function handle(RealmSocialProvider $provider, array $config, bool $enabled): void;
}

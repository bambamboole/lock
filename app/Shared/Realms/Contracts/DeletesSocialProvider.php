<?php
declare(strict_types=1);

namespace App\Shared\Realms\Contracts;

use App\Realms\Models\RealmSocialProvider;

interface DeletesSocialProvider
{
    public function handle(RealmSocialProvider $provider): void;
}

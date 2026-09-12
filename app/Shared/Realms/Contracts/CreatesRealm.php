<?php
declare(strict_types=1);

namespace App\Shared\Realms\Contracts;

use App\Realms\Models\Realm;

interface CreatesRealm
{
    /** @param array<string, mixed> $configuration Settings that deviate from the defaults a new realm otherwise follows. */
    public function handle(string $name, string $domain, string $slug, array $configuration = []): Realm;
}

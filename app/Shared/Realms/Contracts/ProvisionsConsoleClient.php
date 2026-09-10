<?php
declare(strict_types=1);

namespace App\Shared\Realms\Contracts;

use App\Realms\Models\Realm;
use App\Shared\Console\Enums\BootstrapOutcome;
use SensitiveParameter;

interface ProvisionsConsoleClient
{
    /**
     * Brings the realm's first-party client in line with the given id, secret
     * and metadata, creating it when it does not exist yet.
     *
     * @param  list<string>  $redirectUris
     * @param  list<string>  $postLogoutRedirectUris
     */
    public function handle(
        Realm $realm,
        string $name,
        string $clientId,
        #[SensitiveParameter] string $clientSecret,
        bool $trusted,
        array $redirectUris,
        array $postLogoutRedirectUris,
    ): BootstrapOutcome;
}

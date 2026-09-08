<?php
declare(strict_types=1);

namespace App\Auth\Support;

use Bambamboole\LaravelOidc\Server\Contracts\ScopeCatalog;

/**
 * Serves the OAuth scope catalog straight from the permission catalog: every
 * tenant-scoped `#[AsScope]`-tagged permission, translated at read time.
 * Global permissions never appear here.
 */
final readonly class OidcScopeCatalog implements ScopeCatalog
{
    public function __construct(private PermissionRepository $permissions) {}

    /**
     * @return array<string, string>
     */
    public function scopes(): array
    {
        return $this->permissions->apiScopes();
    }
}

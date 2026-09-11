<?php

declare(strict_types=1);

namespace App\Shared\Auth\Contracts;

use App\Shared\Auth\Data\PermissionDefinition;
use InvalidArgumentException;

/**
 * The application's permission catalog, discovered from every `#[AsPermission]`
 * enum. Auth owns the discovery and caching; every other domain reads through
 * this contract.
 */
interface PermissionCatalog
{
    /** @return list<PermissionDefinition> */
    public function realm(): array;

    /** @return list<PermissionDefinition> */
    public function global(): array;

    public function find(string $value): ?PermissionDefinition;

    /**
     * @throws InvalidArgumentException when the value names no permission
     */
    public function get(string $value): PermissionDefinition;

    public function isGlobal(string $value): bool;

    /** @return list<string> */
    public function values(): array;

    /**
     * Drops the cached catalog, rediscovers the enums and re-primes the cache.
     * Returns the number of permissions in the rebuilt catalog.
     */
    public function rebuild(): int;
}

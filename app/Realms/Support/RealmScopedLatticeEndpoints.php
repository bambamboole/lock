<?php

declare(strict_types=1);

namespace App\Realms\Support;

use Bambamboole\LaravelOidc\Server\Realms\Http\Middleware\ResolveRealm;
use Illuminate\Http\Request;

/**
 * Below `/realms/{realm}` the provider session lives in a cookie scoped to
 * that path, so a page rendered there can only call Lattice endpoints that
 * live below it as well. Every Lattice URL minted while a realm request is
 * being served is therefore moved under the realm; `routes/web.php` registers
 * the matching endpoints. Root requests keep the root endpoints.
 */
final readonly class RealmScopedLatticeEndpoints
{
    public function __construct(private Request $request) {}

    public function format(string $path): string
    {
        $realm = $this->request->attributes->get(ResolveRealm::ATTRIBUTE);

        if (! is_string($realm) || ! str_starts_with($path, '/lattice/')) {
            return $path;
        }

        return '/realms/'.$realm.$path;
    }
}

<?php

declare(strict_types=1);

namespace App\Realms\Support;

use Bambamboole\LaravelOidc\Server\Realms\Http\Middleware\ResolveRealm;
use Illuminate\Http\Request;

/**
 * A page a realm serves is signed in through the identity guard, which the
 * root Lattice endpoints do not consult. Every Lattice URL minted while such
 * a request is being served is therefore moved below `/account`, where
 * `routes/web.php` registers the same endpoints behind that guard. Console
 * requests keep the root endpoints.
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

        return '/account'.$path;
    }
}

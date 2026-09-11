<?php

declare(strict_types=1);

namespace App\Shared\Realms\Support;

use Illuminate\Http\Request;

/**
 * The routes a realm serves from its own host: the provider's sign-in and
 * protocol endpoints, the account page and the Lattice endpoints below it.
 * On these the host decides the realm; everywhere else the realm is data or
 * the signed-in user's own.
 */
final class RealmRoutes
{
    /** @var list<string> */
    public const array SERVED = ['oidc.*', 'identity.*', 'account', 'realm.lattice.*'];

    /**
     * Realm-neutral routes a realm's own pages still need: the translations
     * the sign-in pages load and the domain check the console probes.
     *
     * @var list<string>
     */
    public const array SHARED = ['i18next.*', 'realm.domain-check'];

    public static function serves(Request $request): bool
    {
        return $request->route() !== null && $request->routeIs(...self::SERVED);
    }
}

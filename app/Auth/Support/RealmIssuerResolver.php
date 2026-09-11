<?php

declare(strict_types=1);

namespace App\Auth\Support;

use App\Realms\Models\Realm;
use Bambamboole\LaravelOidc\Server\Shared\Realms\IssuerResolver;
use Bambamboole\LaravelOidc\Server\Shared\Realms\RealmResolver;

/**
 * The package's resolver takes the request host, and outside a request falls
 * back to APP_URL — so a logout token the scheduler signs for another realm
 * would name the master realm as its issuer. The realm's stored domain is the
 * issuer wherever the token is minted.
 */
final readonly class RealmIssuerResolver implements IssuerResolver
{
    public function __construct(private RealmResolver $realms) {}

    public function url(): string
    {
        $realm = $this->realms->current();

        return $realm instanceof Realm ? $realm->origin() : rtrim((string) config('app.url'), '/');
    }
}

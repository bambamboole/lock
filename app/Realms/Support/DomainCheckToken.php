<?php

declare(strict_types=1);

namespace App\Realms\Support;

/**
 * What this instance answers on a host's domain check. Keyed with APP_KEY, so
 * only a Lock sharing this instance's key — every replica of it — gives the
 * same answer; another Lock or any other server on the domain does not.
 */
final class DomainCheckToken
{
    public static function for(string $host): string
    {
        return hash_hmac('sha256', 'lock-domain-check|'.strtolower($host), (string) config('app.key'));
    }
}

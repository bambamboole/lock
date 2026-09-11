<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Realms\Models\Realm;
use App\Realms\Support\DomainCheckToken;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * The submission of one realm setting as `RealmSettingForm` receives it:
 * the payload and the context that names the field, ready to spread into
 * `submitForm()`.
 *
 * @return array{0: array<string, mixed>, 1: array<string, string>}
 */
function realmSetting(Realm $realm, string $field, mixed $value): array
{
    return [[$field => $value], ['realm' => $realm->slug, 'field' => $field]];
}

/**
 * A URL on the realm's own host, the way a browser addresses the realm.
 */
function realmUrl(Realm|string $realm, string $path = ''): string
{
    $realm = $realm instanceof Realm ? $realm : Realm::query()->where('slug', $realm)->sole();

    return $path === '' ? $realm->origin() : $realm->origin().'/'.ltrim($path, '/');
}

/**
 * A named route of the pages a realm serves, minted with the realm current
 * so it lands on the realm's host.
 *
 * @param  array<string, mixed>  $parameters
 */
function realmRoute(Realm|string $realm, string $name, array $parameters = []): string
{
    $realm = $realm instanceof Realm ? $realm : Realm::query()->where('slug', $realm)->sole();

    return $realm->runAsCurrent(fn (): string => route($name, $parameters));
}

/**
 * Answers every domain check the way this instance answers it on its own
 * hosts, as if DNS and the proxy already led every domain here.
 */
function domainReachesThisInstance(): void
{
    Http::fake(fn (Request $request) => Http::response(DomainCheckToken::for((string) parse_url($request->url(), PHP_URL_HOST))));
}

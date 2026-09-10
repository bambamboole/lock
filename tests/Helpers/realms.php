<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Realms\Models\Realm;

/**
 * The configuration form payload for a realm as the form itself would submit
 * it: list settings joined line by line, overrides on top.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function realmFormData(Realm $realm, array $overrides = []): array
{
    $data = $realm->configuration();

    foreach (['allowed_redirect_schemes', 'allowed_redirect_domains', 'default_scopes', 'trusted_clients'] as $key) {
        $data[$key] = implode("\n", array_filter($realm->settings->array('realm.'.$key), is_string(...)));
    }

    return [...$data, ...$overrides];
}

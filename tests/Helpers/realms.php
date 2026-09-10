<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Realms\Models\Realm;

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

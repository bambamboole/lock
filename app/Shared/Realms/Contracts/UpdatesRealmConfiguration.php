<?php
declare(strict_types=1);

namespace App\Shared\Realms\Contracts;

use App\Realms\Models\Realm;
use Illuminate\Validation\ValidationException;

interface UpdatesRealmConfiguration
{
    /**
     * Applies the submitted subset of the realm name and configuration keys
     * from RealmConfiguration::defaults(); unknown keys are ignored.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function handle(Realm $realm, array $data): void;
}

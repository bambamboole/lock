<?php
declare(strict_types=1);

namespace App\Realms\Actions;

use App\Realms\Models\Realm;
use App\Shared\Realms\Contracts\CreatesRealm;
use Illuminate\Support\Facades\DB;

final class CreateRealm implements CreatesRealm
{
    /**
     * Creates the realm and its preset roles. The realm starts empty: its
     * users are created inside it afterwards, because a user cannot be moved
     * into a realm from outside one.
     */
    public function handle(string $name, ?string $slug = null): Realm
    {
        return DB::transaction(fn (): Realm => Realm::create(array_filter([
            'name' => $name,
            'slug' => $slug,
        ])));
    }
}

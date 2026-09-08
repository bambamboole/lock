<?php
declare(strict_types=1);

namespace App\Shared\Exceptions;

use RuntimeException;

final class MissingRealmContext extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'No realm_id in Context. Make a realm current via Realm::makeCurrent() or Realm::runAsCurrent(), '
            .'or use withoutGlobalScope(RealmScope::class) for deliberate cross-realm queries.'
        );
    }
}

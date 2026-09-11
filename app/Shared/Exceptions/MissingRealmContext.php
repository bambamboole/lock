<?php
declare(strict_types=1);

namespace App\Shared\Exceptions;

use RuntimeException;

final class MissingRealmContext extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('No realm is current. Make one current via Realm::makeCurrent() or Realm::runAsCurrent().');
    }
}

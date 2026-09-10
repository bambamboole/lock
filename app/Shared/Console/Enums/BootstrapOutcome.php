<?php
declare(strict_types=1);

namespace App\Shared\Console\Enums;

enum BootstrapOutcome: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Unchanged = 'unchanged';
}

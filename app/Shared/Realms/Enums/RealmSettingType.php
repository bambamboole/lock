<?php

declare(strict_types=1);

namespace App\Shared\Realms\Enums;

/**
 * What a realm setting holds, which is what both its input and its rendered
 * value are derived from. The distinction between a duration, a day count and
 * a plain count is not visible in the stored `int`, so it is declared here
 * rather than guessed from the value.
 */
enum RealmSettingType
{
    case Duration;
    case Days;
    case Count;
    case Text;
    case Flag;
    case StringList;
    case Factors;
}

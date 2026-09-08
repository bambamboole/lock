<?php
declare(strict_types=1);

namespace App\Auth\Contracts;

use BackedEnum;

/**
 * Contract every `#[AsPermission]` enum must implement — usually via the
 * `App\Auth\Concerns\HasPermissionLabel` trait, which provides every method.
 */
interface PermissionEnum extends BackedEnum
{
    public function getTranslationKey(): string;

    public function getLabel(): string;

    public function getDescription(): string;
}

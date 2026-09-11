<?php
declare(strict_types=1);

namespace App\Shared\Auth\Contracts;

use BackedEnum;

interface PermissionEnum extends BackedEnum
{
    public function getTranslationKey(): string;

    public function getLabel(): string;

    public function getDescription(): string;
}

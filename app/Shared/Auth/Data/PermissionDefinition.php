<?php
declare(strict_types=1);

namespace App\Shared\Auth\Data;

use App\Shared\Auth\Contracts\PermissionEnum;

final readonly class PermissionDefinition
{
    /**
     * @param  string  $group  Translation key for the permission's group label.
     * @param  string  $descriptionKey  Translation key for the permission's description.
     */
    public function __construct(
        public PermissionEnum $case,
        public string $value,
        public string $group,
        public bool $global,
        public string $descriptionKey,
    ) {}
}

<?php
declare(strict_types=1);

namespace App\Auth\Support;

use App\Auth\Contracts\PermissionEnum;

final readonly class PermissionDefinition
{
    /**
     * @param  string  $group  Translation key for the permission's group label.
     * @param  string  $descriptionKey  Translation key for the permission's description.
     * @param  bool  $isScope  Whether the permission is exposed as an OAuth API scope.
     */
    public function __construct(
        public PermissionEnum $case,
        public string $value,
        public string $group,
        public bool $global,
        public string $descriptionKey,
        public bool $isScope,
    ) {}
}

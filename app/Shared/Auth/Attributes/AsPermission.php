<?php
declare(strict_types=1);

namespace App\Shared\Auth\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AsPermission
{
    /**
     * @param  string  $label  Translation key for the group heading the enum's cases are listed under.
     * @param  bool  $global  Whether the enum's cases grant application-wide capabilities instead of realm-scoped ones.
     */
    public function __construct(
        public string $label,
        public bool $global = false,
    ) {}
}

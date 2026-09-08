<?php
declare(strict_types=1);

namespace App\Auth\Attributes;

use Attribute;

/**
 * Marks a tenant permission enum case as exposed through the OAuth API scope
 * catalog. Untagged cases remain UI/gate-only permissions. Global permissions
 * never become API scopes — tagging one fails permission discovery.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
final readonly class AsScope {}

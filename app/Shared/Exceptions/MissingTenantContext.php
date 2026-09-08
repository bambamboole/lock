<?php
declare(strict_types=1);

namespace App\Shared\Exceptions;

use RuntimeException;

final class MissingTenantContext extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'No tenant_id in Context. Make a tenant current via Tenant::makeCurrent() or Tenant::runAsCurrent(), '
            .'or use withoutGlobalScope(TenantScope::class) for deliberate cross-tenant queries.'
        );
    }
}

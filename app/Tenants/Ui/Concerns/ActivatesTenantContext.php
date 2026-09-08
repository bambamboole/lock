<?php
declare(strict_types=1);

namespace App\Tenants\Ui\Concerns;

use App\Tenants\Models\Tenant;
use Illuminate\Http\Request;

/**
 * A signed definition endpoint runs none of the page's route middleware, so the
 * tenant the page made current is re-established here, request-wide.
 */
trait ActivatesTenantContext
{
    public function activated(Request $request): void
    {
        /** @var Tenant $tenant */
        $tenant = $this->contextModel('tenant');

        $tenant->makeCurrent();
    }
}

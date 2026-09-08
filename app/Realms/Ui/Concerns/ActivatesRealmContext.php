<?php
declare(strict_types=1);

namespace App\Realms\Ui\Concerns;

use App\Realms\Models\Realm;
use Illuminate\Http\Request;

/**
 * A signed definition endpoint runs none of the page's route middleware, so the
 * realm the page made current is re-established here, request-wide.
 */
trait ActivatesRealmContext
{
    public function activated(Request $request): void
    {
        /** @var Realm $realm */
        $realm = $this->contextModel('realm');

        $realm->makeCurrent();
    }
}

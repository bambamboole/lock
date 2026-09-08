<?php

declare(strict_types=1);

namespace App\Billing\Ui\Actions\Concerns;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Laravel\Paddle\Exceptions\PaddleException;
use Lattice\Actions\ActionResult;

trait HandlesPaddleFailures
{
    /**
     * @param  Closure(): ActionResult  $callback
     */
    protected function attemptPaddleAction(Closure $callback): ActionResult
    {
        try {
            return $callback();
        } catch (PaddleException|ConnectionException $exception) {
            report($exception);

            return ActionResult::failure(__('billing.manage.provider-error'));
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Realms\Http\Controllers;

use App\Realms\Support\DomainCheckToken;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Answers on every host, a realm's or not, so a domain can be probed before
 * its realm exists and whatever the host gate would make of it.
 */
final class DomainCheckController
{
    public function __invoke(Request $request): Response
    {
        return response(DomainCheckToken::for($request->getHost()), 200, [
            'Content-Type' => 'text/plain',
            'Cache-Control' => 'no-store',
        ]);
    }
}

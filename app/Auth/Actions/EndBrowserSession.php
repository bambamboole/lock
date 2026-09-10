<?php

declare(strict_types=1);

namespace App\Auth\Actions;

use App\Auth\Models\User;
use App\Auth\Support\BrowserSession;
use App\Auth\Support\BrowserSessions;
use App\Shared\Auth\Contracts\EndsUserSessions;
use Illuminate\Support\Facades\DB;

/**
 * Ends one of the user's other browser sessions: the row goes, so that browser
 * is signed out at the provider, and the OIDC session it started is ended the
 * way an administrator ends one — revoked and its relying parties notified.
 */
final readonly class EndBrowserSession
{
    public function __construct(
        private BrowserSessions $sessions,
        private EndsUserSessions $endUserSessions,
    ) {}

    public function handle(User $user, string $sessionId): bool
    {
        $session = $this->sessions->find($user, $sessionId);

        if (! $session instanceof BrowserSession) {
            return false;
        }

        DB::connection(config('session.connection'))
            ->table((string) config('session.table', 'sessions'))
            ->where('id', $session->id)
            ->delete();

        $sid = $session->oidcSessionId();

        if ($sid !== null) {
            $this->endUserSessions->handle($user, $sid);
        }

        return true;
    }
}

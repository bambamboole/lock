<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

it('schedules every maintenance command laravel-oidc leaves to the application', function () {
    $scheduled = implode("\n", array_map(fn (Event $event): string => (string) $event->command, app(Schedule::class)->events()));

    foreach (['oidc:purge', 'oidc:dispatch-expired-session-logouts', 'oidc:prune-authentication-contexts', 'oidc:prune-sessions'] as $command) {
        expect($scheduled)->toContain($command);
    }
});

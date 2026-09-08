<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Schedule;

it('schedules oidc expiry dispatch before persistence pruning', function () {
    $commands = collect(app(Schedule::class)->events())
        ->map(fn ($event): string => $event->command ?? '')
        ->values();

    $positionOf = function (string $needle) use ($commands): int {
        $position = $commands->search(fn (string $command): bool => str_contains($command, $needle));

        if (! is_int($position)) {
            throw new RuntimeException("The schedule does not contain [{$needle}].");
        }

        return $position;
    };

    expect($positionOf('oidc:dispatch-expired-session-logouts'))
        ->toBeLessThan($positionOf('oidc:prune-authentication-contexts'));
});

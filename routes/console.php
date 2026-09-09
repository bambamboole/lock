<?php
declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('activitylog:clean')->daily();
Schedule::command('oidc:purge')->daily();
Schedule::command('oidc:dispatch-expired-session-logouts')->hourly();
Schedule::command('oidc:prune-authentication-contexts')->daily();
Schedule::command('lattice:notifications:prune')->daily();

<?php
declare(strict_types=1);

use App\Audit\Models\AdminEvent;
use App\Audit\Models\UserEvent;
use Illuminate\Support\Facades\Schedule;

Schedule::command('model:prune', ['--model' => [AdminEvent::class, UserEvent::class]])->daily();
Schedule::command('oidc:purge')->daily();
Schedule::command('oidc:dispatch-expired-session-logouts')->hourly();
Schedule::command('oidc:prune-authentication-contexts')->daily();
Schedule::command('oidc:prune-sessions')->daily();
Schedule::command('lattice:notifications:prune')->daily();

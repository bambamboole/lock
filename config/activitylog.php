<?php
declare(strict_types=1);

use App\Realms\Models\Activity;

// Only the keys this app changes from the package defaults live here.
return [

    'clean_after_days' => (int) env('ACTIVITYLOG_CLEAN_AFTER_DAYS', 365),

    'activity_model' => Activity::class,

];

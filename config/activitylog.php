<?php
declare(strict_types=1);

use App\Shared\Audit\Models\Activity;

return [

    'clean_after_days' => (int) env('ACTIVITYLOG_CLEAN_AFTER_DAYS', 365),

    'activity_model' => Activity::class,

];

<?php

declare(strict_types=1);
use Relaticle\ActivityLog\Models\Activity;

return [
    'enabled' => env('ACTIVITYLOG_ENABLED', true),
    'clean_after_days' => 365,
    'default_log_name' => 'crm',
    'default_auth_driver' => null,
    'include_soft_deleted_subjects' => true,
    'activity_model' => Activity::class,
];

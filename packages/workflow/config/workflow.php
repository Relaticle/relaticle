<?php

declare(strict_types=1);

return [
    'queue' => env('WORKFLOW_QUEUE', 'default'),
    'table_prefix' => env('WORKFLOW_TABLE_PREFIX', ''),
    'max_steps_per_run' => env('WORKFLOW_MAX_STEPS', 100),
    'max_loop_iterations' => env('WORKFLOW_MAX_LOOP', 500),
    'retry_attempts' => env('WORKFLOW_RETRY_ATTEMPTS', 3),
    'enable_audit_trail' => true,
    'middleware' => ['web', 'auth'],
];

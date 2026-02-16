<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;
use Spatie\Health\Commands\DispatchQueueCheckJobsCommand;
use Spatie\Health\Commands\RunHealthChecksCommand;
use Spatie\Health\Commands\ScheduleCheckHeartbeatCommand;

if (config('app.health_checks_enabled')) {
    Schedule::command(RunHealthChecksCommand::class)->everyMinute();
    Schedule::command(DispatchQueueCheckJobsCommand::class)->everyMinute();
    Schedule::command(ScheduleCheckHeartbeatCommand::class)->everyMinute();
}

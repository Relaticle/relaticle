<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Relaticle\Workflow\Enums\WorkflowRunStatus;
use Relaticle\Workflow\Models\Workflow;
use Relaticle\Workflow\Models\WorkflowRun;

class WorkflowStatsWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    /**
     * Returns the raw workflow metrics as an associative array.
     *
     * @return array{total_runs: int, success_rate: float, active_workflows: int, failed_runs: int}
     */
    public function getMetrics(): array
    {
        $totalRuns = WorkflowRun::count();
        $completedRuns = WorkflowRun::where('status', WorkflowRunStatus::Completed)->count();
        $failedRuns = WorkflowRun::where('status', WorkflowRunStatus::Failed)->count();
        $activeWorkflows = Workflow::where('is_active', true)->count();
        $successRate = $totalRuns > 0 ? round(($completedRuns / $totalRuns) * 100, 1) : 0.0;

        return [
            'total_runs' => $totalRuns,
            'success_rate' => $successRate,
            'active_workflows' => $activeWorkflows,
            'failed_runs' => $failedRuns,
        ];
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $metrics = $this->getMetrics();

        return [
            Stat::make('Total Workflow Runs', (string) $metrics['total_runs'])
                ->description('All time')
                ->icon('heroicon-o-play'),
            Stat::make('Success Rate', $metrics['success_rate'] . '%')
                ->description($metrics['failed_runs'] . ' failed')
                ->icon('heroicon-o-check-circle')
                ->color($metrics['success_rate'] >= 90 ? 'success' : ($metrics['success_rate'] >= 70 ? 'warning' : 'danger')),
            Stat::make('Active Workflows', (string) $metrics['active_workflows'])
                ->description('Currently enabled')
                ->icon('heroicon-o-bolt'),
        ];
    }
}

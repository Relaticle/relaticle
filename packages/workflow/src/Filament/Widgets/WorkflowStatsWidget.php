<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Relaticle\Workflow\Enums\WorkflowRunStatus;
use Relaticle\Workflow\Enums\WorkflowStatus;
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
        // Use Workflow's global scope to only get tenant's workflows
        $workflowIds = Workflow::pluck('id');

        $totalRuns = WorkflowRun::whereIn('workflow_id', $workflowIds)->count();
        $completedRuns = WorkflowRun::whereIn('workflow_id', $workflowIds)
            ->where('status', WorkflowRunStatus::Completed)->count();
        $failedRuns = WorkflowRun::whereIn('workflow_id', $workflowIds)
            ->where('status', WorkflowRunStatus::Failed)->count();
        $activeWorkflows = Workflow::where('status', WorkflowStatus::Live)->count();
        $successRate = $totalRuns > 0 ? round(($completedRuns / $totalRuns) * 100, 1) : 0;

        return compact('totalRuns', 'completedRuns', 'failedRuns', 'activeWorkflows', 'successRate');
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $metrics = $this->getMetrics();

        return [
            Stat::make('Total Workflow Runs', (string) $metrics['totalRuns'])
                ->description('All time')
                ->icon('heroicon-o-play'),
            Stat::make('Success Rate', $metrics['successRate'] . '%')
                ->description($metrics['failedRuns'] . ' failed')
                ->icon('heroicon-o-check-circle')
                ->color($metrics['successRate'] >= 90 ? 'success' : ($metrics['successRate'] >= 70 ? 'warning' : 'danger')),
            Stat::make('Active Workflows', (string) $metrics['activeWorkflows'])
                ->description('Currently enabled')
                ->icon('heroicon-o-bolt'),
        ];
    }
}

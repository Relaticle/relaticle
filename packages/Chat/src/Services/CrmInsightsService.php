<?php

declare(strict_types=1);

namespace Relaticle\Chat\Services;

use App\Models\Company;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Relaticle\Chat\Data\CrmInsight;

final readonly class CrmInsightsService
{
    private const int MAX_INSIGHTS = 5;

    private const int CACHE_TTL_MINUTES = 5;

    /**
     * @return Collection<int, CrmInsight>
     */
    public function forTeam(Team $team): Collection
    {
        return Cache::remember(
            "crm_insights_{$team->getKey()}",
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn (): Collection => $this->compute($team),
        );
    }

    /**
     * @return Collection<int, CrmInsight>
     */
    private function compute(Team $team): Collection
    {
        /** @var Collection<int, CrmInsight> $insights */
        $insights = collect();

        $stalled = $this->stalledDeals($team);
        if ($stalled instanceof CrmInsight) {
            $insights->push($stalled);
        }

        $cold = $this->coldContacts($team);
        if ($cold instanceof CrmInsight) {
            $insights->push($cold);
        }

        $overdue = $this->overdueTasks($team);
        if ($overdue instanceof CrmInsight) {
            $insights->push($overdue);
        }

        $wins = $this->recentWins($team);
        if ($wins instanceof CrmInsight) {
            $insights->push($wins);
        }

        $pipelineValue = $this->pipelineValue($team);
        if ($pipelineValue instanceof CrmInsight) {
            $insights->push($pipelineValue);
        }

        $newCompanies = $this->newCompanies($team);
        if ($newCompanies instanceof CrmInsight) {
            $insights->push($newCompanies);
        }

        $newPeople = $this->newPeople($team);
        if ($newPeople instanceof CrmInsight) {
            $insights->push($newPeople);
        }

        $newOpportunities = $this->newOpportunities($team);
        if ($newOpportunities instanceof CrmInsight) {
            $insights->push($newOpportunities);
        }

        return $insights->take(self::MAX_INSIGHTS)->values();
    }

    private function stalledDeals(Team $team): ?CrmInsight
    {
        $count = Opportunity::query()
            ->whereBelongsTo($team)
            ->where('updated_at', '<', now()->subDays(30))
            ->count();

        if ($count === 0) {
            return null;
        }

        return new CrmInsight(
            key: 'stalled-deals',
            title: 'Stalled deals',
            description: "{$count} deals haven't moved in 30+ days",
            count: $count,
            severity: 'warning',
            prompt: 'Show me opportunities that haven\'t been updated in the last 30 days',
        );
    }

    private function coldContacts(Team $team): ?CrmInsight
    {
        $count = People::query()
            ->whereBelongsTo($team)
            ->where('updated_at', '<', now()->subDays(60))
            ->count();

        if ($count === 0) {
            return null;
        }

        return new CrmInsight(
            key: 'cold-contacts',
            title: 'Cold contacts',
            description: "{$count} people you haven't engaged in 60+ days",
            count: $count,
            severity: 'warning',
            prompt: 'Show me people I haven\'t engaged with in 60 days',
        );
    }

    private function newCompanies(Team $team): ?CrmInsight
    {
        $count = Company::query()
            ->whereBelongsTo($team)
            ->where('created_at', '>=', now()->startOfWeek())
            ->count();

        if ($count === 0) {
            return null;
        }

        return new CrmInsight(
            key: 'new-companies',
            title: 'New companies',
            description: "{$count} new companies this week",
            count: $count,
            severity: 'info',
            prompt: 'Show companies added this week',
        );
    }

    private function newPeople(Team $team): ?CrmInsight
    {
        $count = People::query()
            ->whereBelongsTo($team)
            ->where('created_at', '>=', now()->startOfWeek())
            ->count();

        if ($count === 0) {
            return null;
        }

        return new CrmInsight(
            key: 'new-people',
            title: 'New contacts',
            description: "{$count} new contacts this week",
            count: $count,
            severity: 'info',
            prompt: 'Show people added this week',
        );
    }

    private function newOpportunities(Team $team): ?CrmInsight
    {
        $count = Opportunity::query()
            ->whereBelongsTo($team)
            ->where('created_at', '>=', now()->startOfWeek())
            ->count();

        if ($count === 0) {
            return null;
        }

        return new CrmInsight(
            key: 'new-opportunities',
            title: 'New opportunities',
            description: "{$count} new opportunities this week",
            count: $count,
            severity: 'success',
            prompt: 'Show opportunities created this week',
        );
    }

    private function overdueTasks(Team $team): ?CrmInsight
    {
        $count = DB::table('custom_field_values as cfv')
            ->join('custom_fields as cf', 'cf.id', '=', 'cfv.custom_field_id')
            ->join('tasks as t', function (JoinClause $join): void {
                $join->on('t.id', '=', 'cfv.entity_id')
                    ->whereNull('t.deleted_at');
            })
            ->where('cf.code', 'due_date')
            ->where('cf.entity_type', Task::class)
            ->where('cf.tenant_id', $team->getKey())
            ->where('t.team_id', $team->getKey())
            ->where('cfv.date_value', '<', now()->toDateString())
            ->count();

        if ($count === 0) {
            return null;
        }

        return new CrmInsight(
            key: 'overdue-tasks',
            title: 'Overdue tasks',
            description: "{$count} tasks past their due date",
            count: $count,
            severity: 'warning',
            prompt: 'Show me my overdue tasks',
        );
    }

    private function recentWins(Team $team): ?CrmInsight
    {
        $count = DB::table('custom_field_values as cfv')
            ->join('custom_fields as cf', 'cf.id', '=', 'cfv.custom_field_id')
            ->join('opportunities as o', function (JoinClause $join): void {
                $join->on('o.id', '=', 'cfv.entity_id')
                    ->whereNull('o.deleted_at');
            })
            ->where('cf.code', 'stage')
            ->where('cf.entity_type', Opportunity::class)
            ->where('cf.tenant_id', $team->getKey())
            ->where('o.team_id', $team->getKey())
            ->whereIn('cfv.string_value', ['Won', 'Closed Won', 'won', 'closed_won'])
            ->where('o.updated_at', '>=', now()->startOfWeek())
            ->count();

        if ($count === 0) {
            return null;
        }

        return new CrmInsight(
            key: 'recent-wins',
            title: 'Recent wins',
            description: "{$count} deals closed this week",
            count: $count,
            severity: 'success',
            prompt: 'Show closed-won deals from this week',
        );
    }

    private function pipelineValue(Team $team): ?CrmInsight
    {
        $sum = (float) DB::table('custom_field_values as cfv')
            ->join('custom_fields as cf', 'cf.id', '=', 'cfv.custom_field_id')
            ->join('opportunities as o', function (JoinClause $join): void {
                $join->on('o.id', '=', 'cfv.entity_id')
                    ->whereNull('o.deleted_at');
            })
            ->where('cf.code', 'amount')
            ->where('cf.entity_type', Opportunity::class)
            ->where('cf.tenant_id', $team->getKey())
            ->where('o.team_id', $team->getKey())
            ->sum(DB::raw('coalesce(cfv.float_value, cfv.integer_value, 0)'));

        if ($sum <= 0.0) {
            return null;
        }

        return new CrmInsight(
            key: 'pipeline-value',
            title: 'Open pipeline',
            description: '$'.number_format($sum, 0).' across active opportunities',
            count: (int) round($sum),
            severity: 'info',
            prompt: 'Show me my open opportunity pipeline by stage',
        );
    }
}

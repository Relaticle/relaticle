<?php

declare(strict_types=1);

namespace Relaticle\Chat\Services;

use App\Models\Company;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Team;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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
}

<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\SubscriberTagEnum;
use App\Jobs\Email\AddSubscriberTagsJob;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\User;

final readonly class OpportunityObserver
{
    public function created(Opportunity $opportunity): void
    {
        if (! config('mailcoach-sdk.enabled_subscribers_sync', false)) {
            return;
        }

        /** @var User|null $user */
        $user = auth()->user();

        if (! $user instanceof User || ! $user->mailcoach_subscriber_uuid) {
            return;
        }

        $teamIds = $user->allTeams()->pluck('id');

        $hasCrmData = Company::query()->whereIn('team_id', $teamIds)->exists()
            || People::query()->whereIn('team_id', $teamIds)->exists()
            || Opportunity::query()->whereIn('team_id', $teamIds)->whereKeyNot($opportunity->getKey())->exists();

        if ($hasCrmData) {
            return;
        }

        dispatch(new AddSubscriberTagsJob(
            $user->mailcoach_subscriber_uuid,
            [SubscriberTagEnum::HAS_CRM_DATA->value],
        ))->afterCommit();
    }

    /**
     * Handle the Opportunity "saved" event.
     * Invalidate AI summary when opportunity data changes.
     */
    public function saved(Opportunity $opportunity): void
    {
        $opportunity->invalidateAiSummary();
    }
}

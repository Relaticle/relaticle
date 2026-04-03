<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\CustomFields\CompanyField;
use App\Enums\SubscriberTagEnum;
use App\Jobs\Email\AddSubscriberTagsJob;
use App\Jobs\FetchFaviconForCompany;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\User;

final readonly class CompanyObserver
{
    public function created(Company $company): void
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

        $hasCrmData = Company::query()->whereIn('team_id', $teamIds)->whereKeyNot($company->getKey())->exists()
            || People::query()->whereIn('team_id', $teamIds)->exists()
            || Opportunity::query()->whereIn('team_id', $teamIds)->exists();

        if ($hasCrmData) {
            return;
        }

        dispatch(new AddSubscriberTagsJob(
            $user->mailcoach_subscriber_uuid,
            [SubscriberTagEnum::HAS_CRM_DATA->value],
        ))->afterCommit();
    }

    public function saved(Company $company): void
    {
        $company->invalidateAiSummary();
        $this->dispatchFaviconFetchIfNeeded($company);
    }

    private function dispatchFaviconFetchIfNeeded(Company $company): void
    {
        $domainField = $company->customFields()
            ->whereBelongsTo($company->team)
            ->where('code', CompanyField::DOMAINS->value)
            ->first();

        if ($domainField === null) {
            return;
        }

        $company->load('customFieldValues.customField.options');

        $domains = $company->getCustomFieldValue($domainField);
        $firstDomain = is_array($domains) ? ($domains[0] ?? null) : $domains;

        if (blank($firstDomain)) {
            return;
        }

        dispatch(new FetchFaviconForCompany($company))->afterCommit();
    }
}

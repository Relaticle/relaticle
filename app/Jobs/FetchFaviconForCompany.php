<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Company;
use AshAllenDesign\FaviconFetcher\Facades\Favicon;
use Illuminate\Contracts\Broadcasting\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

final class FetchFaviconForCompany implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public readonly Company $company)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $customFieldDomain = $this->company->customFields()->where('code', 'domain_name')->first();
            $domainName = $this->company->getCustomFieldValue($customFieldDomain);

            if ($domainName === null) {
                return;
            }

            $favicon = Favicon::withFallback('google-shared-stuff')->fetch($domainName);
            $url = $favicon?->getFaviconUrl();

            if ($url === null) {
                return;
            }

            $logo = $this->company
                ->addMediaFromUrl($url)
                ->toMediaCollection('logo');

            $this->company->clearMediaCollectionExcept('logo', $logo);
        } catch (\Exception $e) {
            $this->company->clearMediaCollection('logo');
        }
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return (string) $this->company->getKey();
    }
}

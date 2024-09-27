<?php

namespace App\Listeners;

use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Features;
use Relaticle\CustomFields\Contracts\CustomsFieldsMigrators;
use Relaticle\CustomFields\Enums\CustomFieldType;

class CreateTeamCustomFields
{
    /**
     * Create the event listener.
     */
    public function __construct(protected CustomsFieldsMigrators $migrator)
    {
    }

    /**
     * Handle the event.
     */
    public function handle(TeamCreated $event): void
    {
        if (Features::hasTeamFeatures()) {
            $team = $event->team;

            // Set the tenant
            $this->migrator->setTenantId($team->id);

            $this->createCustomFieldsForCompany($team);
        }
    }

    private function createCustomFieldsForCompany(Team $team): void
    {
        // ICP - Ideal Customer Profile: Indicates whether the company is the most suitable and valuable customer for you
        $this->migrator
            ->new(
                model: Company::class,
                type: CustomFieldType::TOGGLE,
                name: 'ICP',
                code: 'icp',
            )
            ->create();

        // Domain Name - Indicates the domain name of the company
        $this->migrator
            ->new(
                model: Company::class,
                type: CustomFieldType::LINK,
                name: 'Domain Name',
                code: 'domain_name',
                systemDefined: true // If a field is system-defined, users cannot delete it, they can only deactivate
            )
            ->create();

        // Linkedin - Indicates the LinkedIn profile of the company
        $this->migrator
            ->new(
                model: Company::class,
                type: CustomFieldType::LINK,
                name: 'Linkedin',
                code: 'linkedin',
            )
            ->create();

        // Account Owner - Indicates the account owner of the company
        $this->migrator
            ->new(
                model: Company::class,
                type: CustomFieldType::SELECT,
                name: 'Account Owner',
                code: 'account_owner',
            )
            ->lookupType(User::class)
            ->create();
    }
}

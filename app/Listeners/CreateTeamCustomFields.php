<?php

namespace App\Listeners;

use App\Models\Company;
use App\Models\Note;
use App\Models\People;
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

            $this->createCustomFieldsForCompany();
            $this->createCustomFieldsForNotes();
            $this->createCustomFieldsForPeople();
        }
    }

    /**
     * Create custom fields for the company model.
     * @return void
     */
    private function createCustomFieldsForCompany(): void
    {
        // ICP - Ideal Customer Profile: Indicates whether the company is the most suitable and valuable customer for you
        $this->migrator
            ->new(
                model: Company::class,
                type: CustomFieldType::TOGGLE,
                name: 'ICP',
                code: 'icp',
                section: 'General'
            )
            ->create();

        // Domain Name - Indicates the domain name of the company
        $this->migrator
            ->new(
                model: Company::class,
                type: CustomFieldType::LINK,
                name: 'Domain Name',
                code: 'domain_name',
                section: 'General',
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
                section: 'General'
            )
            ->create();

        // Account Owner - Indicates the account owner of the company
        $this->migrator
            ->new(
                model: Company::class,
                type: CustomFieldType::SELECT,
                name: 'Account Owner',
                code: 'account_owner',
                section: 'General'
            )
            ->lookupType(User::class)
            ->create();
    }

    private function createCustomFieldsForNotes(): void
    {
        // Title - Indicates the title of the note
        $this->migrator
            ->new(
                model: Note::class,
                type: CustomFieldType::TEXT,
                name: 'Title',
                code: 'title',
                section: 'General',
                systemDefined: true
            )
            ->create();

        // Body - Indicates the body of the note
        $this->migrator
            ->new(
                model: Note::class,
                type: CustomFieldType::RICH_EDITOR,
                name: 'Body',
                code: 'body',
                section: 'General',
            )
            ->create();
    }

    /**
     * Create custom fields for the people model.
     * @return void
     */
    private function createCustomFieldsForPeople(): void
    {
        // Emails - Indicates the emails of the people
        $this->migrator
            ->new(
                model: People::class,
                type: CustomFieldType::TAGS_INPUT,
                name: 'Emails',
                code: 'emails',
                section: 'General'
            )
            ->create();

        // Phone Number - Indicate the phone number of the people
        $this->migrator
            ->new(
                model: People::class,
                type: CustomFieldType::TEXT,
                name: 'Phone Number',
                code: 'phone_number',
                section: 'General'
            )
            ->create();

        // Job Title - Indicates the job title of the people
        $this->migrator
            ->new(
                model: People::class,
                type: CustomFieldType::TEXT,
                name: 'Job Title',
                code: 'job_title',
                section: 'General'
            )
            ->create();

        // Linkedin - Indicates the LinkedIn profile of the people
        $this->migrator
            ->new(
                model: People::class,
                type: CustomFieldType::LINK,
                name: 'Linkedin',
                code: 'linkedin',
                section: 'General'
            )
            ->create();
    }
}

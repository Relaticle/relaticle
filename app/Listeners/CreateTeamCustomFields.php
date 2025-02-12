<?php

namespace App\Listeners;

use App\Models\Company;
use App\Models\Note;
use App\Models\People;
use App\Models\Task;
use App\Models\User;
use App\Models\Opportunity;
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

            $this->createCustomFieldsForOpportunity();

            $this->createCustomFieldsForNotes();

            $this->createCustomFieldsForTasks();

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

    /**
     * Create custom fields for the opportunity model.
     * @return void
     */
    private function createCustomFieldsForOpportunity(): void
    {
        // Name - Indicates the name of the opportunity
        $this->migrator
            ->new(
                model: Opportunity::class,
                type: CustomFieldType::TEXT,
                name: 'Name',
                code: 'name',
                section: 'General',
                systemDefined: true
            )
            ->create();

        // Amount - Indicates the amount of the opportunity
        $this->migrator
            ->new(
                model: Opportunity::class,
                type: CustomFieldType::NUMBER,
                name: 'Amount',
                code: 'amount',
                section: 'General',
            )
            ->create();

        // Close Date - Indicates the close date of the opportunity
        $this->migrator
            ->new(
                model: Opportunity::class,
                type: CustomFieldType::DATE,
                name: 'Close Date',
                code: 'close_date',
                section: 'General'
            )
            ->create();

        // Stage - Indicates the stage of the opportunity
        $this->migrator
            ->new(
                model: Opportunity::class,
                type: CustomFieldType::SELECT,
                name: 'Stage',
                code: 'stage',
                section: 'General'
            )
            ->options([
                'Prospecting',
                'Qualification',
                'Needs Analysis',
                'Value Proposition',
                'Id. Decision Makers',
                'Perception Analysis',
                'Proposal/Price Quote',
                'Negotiation/Review',
                'Closed Won',
                'Closed Lost',
            ])
            ->create();

        // Company - Indicates the company of the opportunity
        $this->migrator
            ->new(
                model: Opportunity::class,
                type: CustomFieldType::SELECT,
                name: 'Company',
                code: 'company',
                section: 'General'
            )
            ->lookupType(Company::class)
            ->create();

        // Point of Contact - Indicates the point of contact of the opportunity
        $this->migrator
            ->new(
                model: Opportunity::class,
                type: CustomFieldType::SELECT,
                name: 'Point of Contact',
                code: 'point_of_contact',
                section: 'General'
            )
            ->lookupType(People::class)
            ->create();
    }

    /**
     * Create custom fields for the notes model.
     * @return void
     */
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

    /**
     * Create custom fields for the tasks model.
     * @return void
     */
    private function createCustomFieldsForTasks()
    {
        // Status - Indicates the status of the task
        $this->migrator
            ->new(
                model: Task::class,
                type: CustomFieldType::SELECT,
                name: 'Status',
                code: 'status',
                section: 'General',
                systemDefined: true
            )
            ->options([
                'To do',
                'In progress',
                'Done',
            ])
            ->create();

        // Priority - Indicates the priority of the task
        $this->migrator
            ->new(
                model: Task::class,
                type: CustomFieldType::SELECT,
                name: 'Priority',
                code: 'priority',
                section: 'General',
                systemDefined: true
            )
            ->options([
                'Low',
                'Medium',
                'High',
            ])
            ->create();

        // Description - Indicates the description of the task
        $this->migrator
            ->new(
                model: Task::class,
                type: CustomFieldType::RICH_EDITOR,
                name: 'Description',
                code: 'description',
                section: 'General',
            )
            ->create();

        // Due Date - Indicates the due date of the task
        $this->migrator
            ->new(
                model: Task::class,
                type: CustomFieldType::DATE_TIME,
                name: 'Due Date',
                code: 'due_date',
                section: 'General'
            )
            ->create();

        // Assignee - Indicates the assignee of the task
        $this->migrator
            ->new(
                model: Task::class,
                type: CustomFieldType::SELECT,
                name: 'Assignee',
                code: 'assignee',
                section: 'General',
            )
            ->lookupType(User::class)
            ->create();

        // Company - Indicates the company of the task
        $this->migrator
            ->new(
                model: Task::class,
                type: CustomFieldType::SELECT,
                name: 'Company',
                code: 'company',
                section: 'General'
            )
            ->lookupType(Company::class)
            ->create();
    }
}

<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\Company;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Task;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Features;
use Relaticle\CustomFields\Contracts\CustomsFieldsMigrators;
use Relaticle\CustomFields\Data\CustomFieldData;
use Relaticle\CustomFields\Data\CustomFieldSectionData;
use Relaticle\CustomFields\Data\CustomFieldSettingsData;
use Relaticle\CustomFields\Enums\CustomFieldSectionType;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Enums\CustomFieldWidth;

final readonly class CreateTeamCustomFields
{
    /**
     * Create the event listener.
     */
    public function __construct(private CustomsFieldsMigrators $migrator) {}

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
     */
    private function createCustomFieldsForCompany(): void
    {
        // ICP - Ideal Customer Profile: Indicates whether the company is the most suitable and valuable customer for you
        $this->migrator
            ->new(
                model: Company::class,
                fieldData: new CustomFieldData(
                    name: 'ICP',
                    code: 'icp',
                    type: CustomFieldType::TOGGLE,
                    section: new CustomFieldSectionData(
                        name: 'General',
                        code: 'general',
                        type: CustomFieldSectionType::HEADLESS
                    ),
                    settings: new CustomFieldSettingsData(
                        list_toggleable_hidden: false
                    )
                )
            )
            ->create();

        // Domain Name - Indicates the domain name of the company
        $this->migrator
            ->new(
                model: Company::class,
                fieldData: new CustomFieldData(
                    name: 'Domain Name',
                    code: 'domain_name',
                    type: CustomFieldType::LINK,
                    section: new CustomFieldSectionData(
                        name: 'General',
                        code: 'general',
                        type: CustomFieldSectionType::HEADLESS
                    ),
                    systemDefined: true, // If a field is system-defined, users cannot delete it, they can only deactivate
                    settings: new CustomFieldSettingsData(
                        list_toggleable_hidden: false
                    )
                )
            )
            ->create();

        // Linkedin - Indicates the LinkedIn profile of the company
        $this->migrator
            ->new(
                model: Company::class,
                fieldData: new CustomFieldData(
                    name: 'Linkedin',
                    code: 'linkedin',
                    type: CustomFieldType::LINK,
                    section: new CustomFieldSectionData(
                        name: 'General',
                        code: 'general',
                        type: CustomFieldSectionType::HEADLESS
                    )
                )
            )
            ->create();
    }

    /**
     * Create custom fields for the opportunity model.
     */
    private function createCustomFieldsForOpportunity(): void
    {
        // Amount - Indicates the amount of the opportunity
        $this->migrator
            ->new(
                model: Opportunity::class,
                fieldData: new CustomFieldData(
                    name: 'Amount',
                    code: 'amount',
                    type: CustomFieldType::NUMBER,
                    section: new CustomFieldSectionData(
                        name: 'General',
                        code: 'general',
                        type: CustomFieldSectionType::HEADLESS
                    )
                )
            )
            ->create();

        // Close Date - Indicates the close date of the opportunity
        $this->migrator
            ->new(
                model: Opportunity::class,
                fieldData: new CustomFieldData(
                    name: 'Close Date',
                    code: 'close_date',
                    type: CustomFieldType::DATE,
                    section: new CustomFieldSectionData(
                        name: 'General',
                        code: 'general',
                        type: CustomFieldSectionType::HEADLESS
                    ),
                    settings: new CustomFieldSettingsData(
                        list_toggleable_hidden: false
                    )
                )
            )
            ->create();

        // Stage - Indicates the stage of the opportunity
        $this->migrator
            ->new(
                model: Opportunity::class,
                fieldData: new CustomFieldData(
                    name: 'Stage',
                    code: 'stage',
                    type: CustomFieldType::SELECT,
                    section: new CustomFieldSectionData(
                        name: 'General',
                        code: 'general',
                        type: CustomFieldSectionType::HEADLESS
                    ),
                    settings: new CustomFieldSettingsData(
                        list_toggleable_hidden: false
                    )
                )
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
    }

    /**
     * Create custom fields for the notes model.
     */
    private function createCustomFieldsForNotes(): void
    {
        // Body - Indicates the body of the note
        $this->migrator
            ->new(
                model: Note::class,
                fieldData: new CustomFieldData(
                    name: 'Body',
                    code: 'body',
                    type: CustomFieldType::RICH_EDITOR,
                    section: new CustomFieldSectionData(
                        name: 'General',
                        code: 'general',
                        type: CustomFieldSectionType::HEADLESS
                    )
                )
            )
            ->create();
    }

    /**
     * Create custom fields for the people model.
     */
    private function createCustomFieldsForPeople(): void
    {
        // Emails - Indicates the emails of the people
        $this->migrator
            ->new(
                model: People::class,
                fieldData: new CustomFieldData(
                    name: 'Emails',
                    code: 'emails',
                    type: CustomFieldType::TAGS_INPUT,
                    section: new CustomFieldSectionData(
                        name: 'General',
                        code: 'general',
                        type: CustomFieldSectionType::HEADLESS
                    ),
                    settings: new CustomFieldSettingsData(
                        list_toggleable_hidden: false
                    )
                )
            )
            ->create();

        // Phone Number - Indicate the phone number of the people
        $this->migrator
            ->new(
                model: People::class,
                fieldData: new CustomFieldData(
                    name: 'Phone Number',
                    code: 'phone_number',
                    type: CustomFieldType::TEXT,
                    section: new CustomFieldSectionData(
                        name: 'General',
                        code: 'general',
                        type: CustomFieldSectionType::HEADLESS
                    )
                )
            )
            ->create();

        // Job Title - Indicates the job title of the people
        $this->migrator
            ->new(
                model: People::class,
                fieldData: new CustomFieldData(
                    name: 'Job Title',
                    code: 'job_title',
                    type: CustomFieldType::TEXT,
                    section: new CustomFieldSectionData(
                        name: 'General',
                        code: 'general',
                        type: CustomFieldSectionType::HEADLESS
                    ),
                    settings: new CustomFieldSettingsData(
                        list_toggleable_hidden: false
                    )
                )
            )
            ->create();

        // Linkedin - Indicates the LinkedIn profile of the people
        $this->migrator
            ->new(
                model: People::class,
                fieldData: new CustomFieldData(
                    name: 'Linkedin',
                    code: 'linkedin',
                    type: CustomFieldType::LINK,
                    section: new CustomFieldSectionData(
                        name: 'General',
                        code: 'general',
                        type: CustomFieldSectionType::HEADLESS,
                    )
                )
            )
            ->create();
    }

    /**
     * Create custom fields for the tasks model.
     */
    private function createCustomFieldsForTasks(): void
    {
        // Status - Indicates the status of the task
        $this->migrator
            ->new(
                model: Task::class,
                fieldData: new CustomFieldData(
                    name: 'Status',
                    code: 'status',
                    type: CustomFieldType::SELECT,
                    section: new CustomFieldSectionData(
                        name: 'General',
                        code: 'general',
                        type: CustomFieldSectionType::HEADLESS
                    ),
                    systemDefined: true,
                    width: CustomFieldWidth::_50,
                    settings: new CustomFieldSettingsData(
                        list_toggleable_hidden: false
                    )
                )
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
                fieldData: new CustomFieldData(
                    name: 'Priority',
                    code: 'priority',
                    type: CustomFieldType::SELECT,
                    section: new CustomFieldSectionData(
                        name: 'General',
                        code: 'general',
                        type: CustomFieldSectionType::HEADLESS
                    ),
                    systemDefined: true,
                    width: CustomFieldWidth::_50,
                    settings: new CustomFieldSettingsData(
                        list_toggleable_hidden: false
                    )
                )
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
                fieldData: new CustomFieldData(
                    name: 'Description',
                    code: 'description',
                    type: CustomFieldType::RICH_EDITOR,
                    section: new CustomFieldSectionData(
                        name: 'General',
                        code: 'general',
                        type: CustomFieldSectionType::HEADLESS
                    )
                )
            )
            ->create();

        // Due Date - Indicates the due date of the task
        $this->migrator
            ->new(
                model: Task::class,
                fieldData: new CustomFieldData(
                    name: 'Due Date',
                    code: 'due_date',
                    type: CustomFieldType::DATE_TIME,
                    section: new CustomFieldSectionData(
                        name: 'General',
                        code: 'general',
                        type: CustomFieldSectionType::HEADLESS
                    )
                )
            )
            ->create();
    }
}

<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\CustomFields\Company as CompanyCustomField;
use App\Enums\CustomFields\Note as NoteCustomField;
use App\Enums\CustomFields\Opportunity as OpportunityCustomField;
use App\Enums\CustomFields\People as PeopleCustomField;
use App\Enums\CustomFields\Task as TaskCustomField;
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
use Relaticle\OnboardSeed\SampleDataSeeder;

/**
 * Creates custom fields for a team when it's created
 */
final readonly class CreateTeamCustomFields
{
    /**
     * Maps model classes to their corresponding custom field enum classes
     *
     * @var array<class-string, class-string>
     */
    private const array MODEL_ENUM_MAP = [
        Company::class => CompanyCustomField::class,
        Opportunity::class => OpportunityCustomField::class,
        Note::class => NoteCustomField::class,
        People::class => PeopleCustomField::class,
        Task::class => TaskCustomField::class,
    ];

    /**
     * Create a new event listener instance
     */
    public function __construct(
        private CustomsFieldsMigrators $migrator,
        private SampleDataSeeder $sampleDataSeeder,
    ) {}

    /**
     * Handle the team created event
     */
    public function handle(TeamCreated $event): void
    {
        if (! Features::hasTeamFeatures()) {
            return;
        }

        $team = $event->team;

        // Set the tenant ID for the custom fields migrator
        $this->migrator->setTenantId($team->id);

        // Create custom fields for all models defined in the map
        foreach (self::MODEL_ENUM_MAP as $modelClass => $enumClass) {
            foreach ($enumClass::cases() as $enum) {
                $this->createCustomField($modelClass, $enum);
            }
        }

        if ($team->isPersonalTeam()) {
            $this->sampleDataSeeder->run($team->owner);
        }
    }

    /**
     * Create a custom field using the provided enum configuration
     *
     * @param  string  $model  The model class name
     * @param  object  $enum  The custom field enum instance
     */
    private function createCustomField(string $model, object $enum): void
    {
        // Extract field configuration from the enum
        $fieldData = new CustomFieldData(
            name: $enum->getDisplayName(),
            code: $enum->value,
            type: $enum->getFieldType(),
            section: new CustomFieldSectionData(
                name: 'General',
                code: 'general',
                type: CustomFieldSectionType::HEADLESS
            ),
            systemDefined: $enum->isSystemDefined(),
            width: $enum->getWidth(),
            settings: new CustomFieldSettingsData(
                list_toggleable_hidden: $enum->isListToggleableHidden()
            )
        );

        // Create the migrator for this field
        $migrator = $this->migrator->new(
            model: $model,
            fieldData: $fieldData
        );

        // Add options for select-type fields if available
        $options = $enum->getOptions();
        if ($options !== null) {
            $migrator->options($options);
        }

        // Create the field in the database
        $migrator->create();

    }
}

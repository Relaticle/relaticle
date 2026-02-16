<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\CustomFields\CompanyField as CompanyCustomField;
use App\Enums\CustomFields\NoteField as NoteCustomField;
use App\Enums\CustomFields\OpportunityField as OpportunityCustomField;
use App\Enums\CustomFields\PeopleField as PeopleCustomField;
use App\Enums\CustomFields\TaskField as TaskCustomField;
use App\Models\Company;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Task;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Features;
use Relaticle\CustomFields\Contracts\CustomsFieldsMigrators;
use Relaticle\CustomFields\Data\CustomFieldData;
use Relaticle\CustomFields\Data\CustomFieldOptionSettingsData;
use Relaticle\CustomFields\Data\CustomFieldSectionData;
use Relaticle\CustomFields\Data\CustomFieldSettingsData;
use Relaticle\CustomFields\Enums\CustomFieldSectionType;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\OnboardSeed\OnboardSeeder;

final readonly class CreateTeamCustomFields
{
    /** @var array<class-string, class-string> */
    private const array MODEL_ENUM_MAP = [
        Company::class => CompanyCustomField::class,
        Opportunity::class => OpportunityCustomField::class,
        Note::class => NoteCustomField::class,
        People::class => PeopleCustomField::class,
        Task::class => TaskCustomField::class,
    ];

    public function __construct(
        private CustomsFieldsMigrators $migrator,
        private OnboardSeeder $onboardSeeder,
    ) {}

    public function handle(TeamCreated $event): void
    {
        if (! Features::hasTeamFeatures()) {
            return;
        }

        $team = $event->team;

        $this->migrator->setTenantId($team->id);

        foreach (self::MODEL_ENUM_MAP as $modelClass => $enumClass) {
            foreach ($enumClass::cases() as $enum) {
                $this->createCustomField($modelClass, $enum);
            }
        }

        if ($team->isPersonalTeam()) {
            /** @var Authenticatable $owner */
            $owner = $team->owner;
            $this->onboardSeeder->run($owner);
        }
    }

    private function createCustomField(string $model, CompanyCustomField|OpportunityCustomField|PeopleCustomField|TaskCustomField|NoteCustomField $enum): void
    {
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
                list_toggleable_hidden: $enum->isListToggleableHidden(),
                enable_option_colors: $enum->hasColorOptions(),
                allow_multiple: $enum->allowsMultipleValues(),
                max_values: $enum->getMaxValues(),
                unique_per_entity_type: $enum->isUniquePerEntityType(),
            )
        );

        $migrator = $this->migrator->new(
            model: $model,
            fieldData: $fieldData
        );

        $options = $enum->getOptions();
        if ($options !== null) {
            $migrator->options($options);
        }

        $customField = $migrator->create();

        $this->applyColorsToOptions($customField, $enum);
    }

    private function applyColorsToOptions(CustomField $customField, CompanyCustomField|OpportunityCustomField|PeopleCustomField|TaskCustomField|NoteCustomField $enum): void
    {
        $colorMapping = $enum->getOptionColors();
        if ($colorMapping === null) {
            return;
        }

        $options = $customField->options()->withoutGlobalScopes()->get();

        $updates = $options
            ->filter(fn ($option) => isset($colorMapping[$option->name]))
            ->map(fn ($option) => [
                'id' => $option->getKey(),
                'settings' => json_encode(new CustomFieldOptionSettingsData(color: $colorMapping[$option->name])),
            ])
            ->values()
            ->all();

        if ($updates === []) {
            return;
        }

        $table = $customField->options()->getModel()->getTable();
        $ids = array_column($updates, 'id');
        $cases = [];
        $bindings = [];

        foreach ($updates as $item) {
            $cases[] = 'WHEN id = ? THEN ?';
            $bindings[] = $item['id'];
            $bindings[] = $item['settings'];
        }

        $casesSql = implode(' ', $cases);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $bindings = array_merge($bindings, $ids);

        DB::update(
            "UPDATE \"{$table}\" SET \"settings\" = CASE {$casesSql} END, \"updated_at\" = ? WHERE \"id\" IN ({$placeholders})",
            [...$bindings, now()],
        );
    }
}

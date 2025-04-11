<?php

declare(strict_types=1);

namespace Database\Seeders\SampleData\Support;

use App\Models\Team;
use App\Models\User;
use Database\Seeders\SampleData\Contracts\ModelSeederInterface;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Models\CustomField;

abstract class BaseModelSeeder implements ModelSeederInterface
{
    /**
     * The custom fields collection
     */
    protected Collection $fields;

    /**
     * The model class this seeder handles
     */
    protected string $modelClass;

    /**
     * The field codes to fetch
     *
     * @var array<int, string>
     */
    protected array $fieldCodes = [];

    /**
     * Current team ID
     */
    protected ?int $teamId = null;

    /**
     * Seed model implementation
     *
     * @param  Team  $team  The team to create data for
     * @param  User  $user  The user creating the data
     * @param  array<string, mixed>  $context  Context data from previous seeders
     * @return array<string, mixed> Seeded data for use by subsequent seeders
     */
    abstract protected function seedModel(Team $team, User $user, array $context = []): array;

    /**
     * Initialize the seeder
     */
    public function initialize(): self
    {
        return $this;
    }

    /**
     * Get custom fields for the model
     *
     * @return Collection<string, mixed>
     */
    public function customFields(): Collection
    {
        if (! $this->teamId) {
            return collect();
        }

        return CustomField::query()
            ->whereTenantId($this->teamId)
            ->forEntity($this->modelClass)
            ->whereIn('code', $this->fieldCodes)
            ->get()
            ->keyBy('code');
    }

    /**
     * Run the model seed process
     */
    public function seed(Team $team, User $user, array $context = []): array
    {
        $this->prepareForSeed($team);

        return $this->seedModel($team, $user, $context);
    }

    /**
     * Set team ID for custom fields retrieval
     */
    protected function setTeamId(int $teamId): void
    {
        $this->teamId = $teamId;
    }

    /**
     * Prepare for seeding
     */
    protected function prepareForSeed(Team $team): void
    {
        $this->setTeamId($team->id);
        $this->fields = $this->customFields();
    }

    /**
     * Apply custom fields to a model
     *
     * @param  object  $model  The model to apply fields to
     * @param  array<string, mixed>  $data  The field data
     */
    protected function applyCustomFields(object $model, array $data): void
    {
        foreach ($data as $code => $value) {
            if (isset($this->fields[$code])) {
                $model->saveCustomFieldValue($this->fields[$code], $value);
            }
        }
    }

    /**
     * Get option ID from a custom field by label
     *
     * @param  string  $fieldCode  The field code
     * @param  string  $optionLabel  The option label to find
     * @return mixed The option ID or null if not found
     */
    protected function getOptionId(string $fieldCode, string $optionLabel): mixed
    {
        $field = $this->fields[$fieldCode] ?? null;

        if (! $field || ! $field->options || $field->options->isEmpty()) {
            return null;
        }

        $option = $field->options->firstWhere('label', $optionLabel)
            ?? $field->options->first();

        return $option ? $option->id : null;
    }
}

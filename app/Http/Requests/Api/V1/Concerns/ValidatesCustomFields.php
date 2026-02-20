<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Concerns;

use App\Models\CustomField;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Relaticle\CustomFields\Services\ValidationService;

/**
 * Provides custom field validation rules for API FormRequests.
 *
 * Automatically fetches active custom fields for the entity type and generates
 * Laravel validation rules via the custom-fields package's ValidationService.
 *
 * For STORE requests: validates submitted fields + flags missing required fields.
 * For UPDATE requests: only validates the custom fields actually submitted.
 */
trait ValidatesCustomFields
{
    abstract public function customFieldEntityType(): string;

    /**
     * Get the route parameter name for the entity (used for unique-value validation on updates).
     */
    public function routeParameterName(): string
    {
        return 'id';
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function customFieldRules(): array
    {
        /** @var User $user */
        $user = $this->user();

        if (! $user || ! $user->currentTeam) {
            return [];
        }

        $teamId = $user->currentTeam->getKey();

        $customFields = $this->resolveCustomFields($teamId);

        if ($customFields->isEmpty()) {
            return [];
        }

        $validationService = resolve(ValidationService::class);

        $rules = [];

        foreach ($customFields as $customField) {
            $fieldRules = $validationService->getValidationRules($customField);

            if ($fieldRules !== []) {
                $rules["custom_fields.{$customField->code}"] = $fieldRules;
            }
        }

        return $rules;
    }

    public function isUpdateRequest(): bool
    {
        return in_array($this->method(), ['PUT', 'PATCH'], true);
    }

    private function resolveCustomFields(string $teamId): EloquentCollection
    {
        /** @var array<string, mixed>|null $submittedFields */
        $submittedFields = $this->input('custom_fields');
        $submittedCodes = is_array($submittedFields) ? array_keys($submittedFields) : [];

        $baseQuery = CustomField::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $teamId)
            ->where('entity_type', $this->customFieldEntityType())
            ->active()
            ->with('options');

        if ($this->isUpdateRequest()) {
            if ($submittedCodes === []) {
                return new EloquentCollection;
            }

            return $baseQuery
                ->whereIn('code', $submittedCodes)
                ->get();
        }

        if ($submittedCodes === []) {
            return $baseQuery
                ->whereJsonContains('validation_rules', ['name' => 'required'])
                ->get();
        }

        return $baseQuery
            ->where(function ($query) use ($submittedCodes): void {
                $query->whereIn('code', $submittedCodes)
                    ->orWhereJsonContains('validation_rules', ['name' => 'required']);
            })
            ->get();
    }
}

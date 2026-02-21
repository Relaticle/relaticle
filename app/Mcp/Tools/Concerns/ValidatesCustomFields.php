<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Concerns;

use App\Models\CustomField;
use App\Models\User;
use Relaticle\CustomFields\Services\ValidationService;

/**
 * Provides custom field validation rules for MCP tools.
 *
 * Reuses the same ValidationService as the API FormRequests to ensure
 * consistent validation across all entry points.
 */
trait ValidatesCustomFields
{
    /**
     * @param  array<string, mixed>|null  $submittedFields
     * @return array<string, array<int, mixed>>
     */
    protected function customFieldValidationRules(
        User $user,
        string $entityType,
        ?array $submittedFields = null,
        bool $isUpdate = false,
    ): array {
        if (! $user->currentTeam) {
            return [];
        }

        $teamId = $user->currentTeam->getKey();
        $submittedCodes = is_array($submittedFields) ? array_keys($submittedFields) : [];

        $baseQuery = CustomField::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $teamId)
            ->where('entity_type', $entityType)
            ->where('active', true)
            ->with('options');

        if ($isUpdate) {
            if ($submittedCodes === []) {
                return [];
            }

            $customFields = $baseQuery->whereIn('code', $submittedCodes)->get();
        } elseif ($submittedCodes === []) {
            $customFields = $baseQuery
                ->whereJsonContains('validation_rules', ['name' => 'required'])
                ->get();
        } else {
            $customFields = $baseQuery
                ->where(function (\Illuminate\Contracts\Database\Query\Builder $query) use ($submittedCodes): void {
                    $query->whereIn('code', $submittedCodes)
                        ->orWhereJsonContains('validation_rules', ['name' => 'required']);
                })
                ->get();
        }

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
}

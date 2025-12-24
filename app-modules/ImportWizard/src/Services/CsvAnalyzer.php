<?php

declare(strict_types=1);

namespace Relaticle\ImportWizard\Services;

use Filament\Actions\Imports\ImportColumn;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use League\Csv\Statement;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\ValidationService;
use Relaticle\ImportWizard\Data\ColumnAnalysis;
use Relaticle\ImportWizard\Data\ValueIssue;
use Spatie\LaravelData\DataCollection;

/**
 * Analyzes CSV columns for validation issues using single-pass processing.
 */
final readonly class CsvAnalyzer
{
    public function __construct(
        private CsvService $csvService,
        private ValidationService $validationService,
    ) {}

    /** @return Collection<int, ColumnAnalysis> */
    public function analyze(string $csvPath, array $columnMap, array $importerColumns, ?string $entityType = null): Collection
    {
        $columnLookup = collect($importerColumns)->keyBy(fn (ImportColumn $c): string => $c->getName());
        $customFields = $entityType ? CustomField::where('entity_type', $entityType)->with('options')->get() : collect();
        $filteredMap = collect($columnMap)->filter(fn (?string $v): bool => $v !== null && $v !== '')->all();

        if ($filteredMap === []) {
            return collect();
        }

        // Initialize collectors
        $collectors = [];
        foreach ($filteredMap as $fieldName => $csvColumn) {
            $collectors[$csvColumn] = [
                'csvColumn' => $csvColumn, 'field' => $fieldName, 'values' => [], 'blankCount' => 0, 'total' => 0,
                'column' => $columnLookup->get($fieldName),
                'customField' => str_starts_with($fieldName, 'custom_fields_')
                    ? $customFields->firstWhere('code', str_replace('custom_fields_', '', $fieldName)) : null,
            ];
        }

        // Single pass through CSV
        foreach ((new Statement)->process($this->csvService->createReader($csvPath)) as $record) {
            foreach ($collectors as $csvColumn => &$c) {
                $c['total']++;
                $val = $record[$csvColumn] ?? '';
                $key = $val === null || $val === '' ? '' : (string) $val;
                if ($key === '') {
                    $c['blankCount']++;
                }
                $c['values'][$key] = ($c['values'][$key] ?? 0) + 1;
            }
        }

        return collect($collectors)->map(function (array $c): ColumnAnalysis {
            arsort($c['values']);
            $isRequired = $c['column']?->isMappingRequired() ?? false;
            $rules = $this->getRules($c['column'], $c['customField']);
            $issues = $this->detectIssues($c['values'], $rules, $c['field'], $isRequired);

            return new ColumnAnalysis(
                csvColumnName: $c['csvColumn'],
                mappedToField: $c['field'],
                fieldType: $this->getFieldType($c['column']),
                totalValues: $c['total'],
                uniqueCount: count($c['values']),
                blankCount: $c['blankCount'],
                uniqueValues: $c['values'],
                issues: new DataCollection(ValueIssue::class, $issues),
                isRequired: $isRequired,
            );
        })->values();
    }

    public function validateSingleValue(string $value, string $fieldName, array $importerColumns, ?string $entityType = null): ?string
    {
        if ($value === '') {
            return null;
        }

        $column = collect($importerColumns)->keyBy(fn (ImportColumn $c): string => $c->getName())->get($fieldName);
        $customField = $entityType && str_starts_with($fieldName, 'custom_fields_')
            ? CustomField::where('entity_type', $entityType)->where('code', str_replace('custom_fields_', '', $fieldName))->first()
            : null;

        $rules = $this->getRules($column, $customField);

        if ($rules['isMultiValue'] && $rules['itemRules'] !== []) {
            return $this->validateMultiValue($value, $rules['itemRules'])?->message;
        }

        if ($rules['rules'] === []) {
            return null;
        }

        $validator = Validator::make([$fieldName => $value], [$fieldName => $rules['rules']]);

        return $validator->fails() ? $validator->errors()->first($fieldName) : null;
    }

    private function getRules(?ImportColumn $column, ?CustomField $customField): array
    {
        $rules = $itemRules = [];
        $isMulti = false;

        if ($column) {
            $rules = $this->filterRules($column->getDataValidationRules());
        }

        if ($customField) {
            $cfRules = $this->filterRules($this->validationService->getValidationRules($customField));
            $cfNames = array_map(fn ($r) => is_string($r) ? explode(':', $r, 2)[0] : '', $cfRules);
            $rules = array_merge(
                array_values(array_filter($rules, fn ($r) => ! is_string($r) || ! in_array(explode(':', $r, 2)[0], $cfNames, true))),
                array_values($cfRules)
            );
            $itemRules = $this->validationService->getItemValidationRules($customField);
            $isMulti = $customField->isMultiChoiceField();
        }

        return ['rules' => $rules, 'itemRules' => $itemRules, 'isMultiValue' => $isMulti];
    }

    private function filterRules(array $rules): array
    {
        $skip = ['confirmed', 'same', 'different', 'current_password'];

        return array_filter($rules, fn ($r) => is_string($r) && $r !== 'array' && ! str_starts_with($r, 'array:') && ! in_array(explode(':', $r, 2)[0], $skip, true));
    }

    private function detectIssues(array $values, array $rules, string $fieldName, bool $isRequired): array
    {
        $issues = [];

        foreach ($values as $value => $count) {
            $value = (string) $value;

            if ($value === '') {
                if ($isRequired) {
                    $issues[] = new ValueIssue($value, 'This field is required', $count, 'error');
                }

                continue;
            }

            if ($fieldName === 'id' && ! Str::isUlid($value)) {
                $issues[] = new ValueIssue($value, 'Invalid ID format. Must be a valid ULID.', $count, 'error');

                continue;
            }

            if ($rules['isMultiValue'] && $rules['itemRules'] !== []) {
                if ($issue = $this->validateMultiValue($value, $rules['itemRules'], $count)) {
                    $issues[] = $issue;
                }

                continue;
            }

            if ($rules['rules'] !== []) {
                $v = Validator::make([$fieldName => $value], [$fieldName => $rules['rules']]);
                if ($v->fails()) {
                    $issues[] = new ValueIssue($value, $v->errors()->first($fieldName), $count, 'error');
                }
            }
        }

        return $issues;
    }

    private function validateMultiValue(string $value, array $itemRules, int $count = 1): ?ValueIssue
    {
        $errors = [];
        foreach (array_map(trim(...), explode(',', $value)) as $item) {
            if ($item !== '' && ($v = Validator::make(['i' => $item], ['i' => $itemRules]))->fails()) {
                $errors[] = $v->errors()->first('i');
            }
        }

        return $errors !== [] ? new ValueIssue($value, implode('; ', array_unique($errors)), $count, 'error') : null;
    }

    private function getFieldType(?ImportColumn $column): string
    {
        if (! $column) {
            return 'string';
        }
        if ($column->isBoolean()) {
            return 'boolean';
        }
        if ($column->isNumeric()) {
            return 'numeric';
        }

        $rules = implode('|', array_map(strval(...), $column->getDataValidationRules()));

        return match (true) {
            str_contains($rules, 'email') => 'email',
            str_contains($rules, 'date') || str_contains($rules, 'Date') => 'date',
            str_contains($rules, 'url') || str_contains($rules, 'URL') => 'url',
            str_contains($rules, 'numeric') || str_contains($rules, 'integer') => 'numeric',
            default => 'string',
        };
    }
}

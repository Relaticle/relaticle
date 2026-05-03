<?php

declare(strict_types=1);

namespace App\Observers;

use Illuminate\Support\Carbon;
use Relaticle\CustomFields\Enums\FieldDataType;
use Relaticle\CustomFields\Facades\CustomFieldsType;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldOption;
use Relaticle\CustomFields\Models\CustomFieldValue;

final class CustomFieldValueObserver
{
    public function created(CustomFieldValue $value): void
    {
        $this->log($value, old: null);
    }

    public function updated(CustomFieldValue $value): void
    {
        $column = CustomFieldValue::getValueColumn($value->customField->type);

        if (! $value->wasChanged($column)) {
            return;
        }

        $this->log($value, old: $value->getOriginal($column));
    }

    private function log(CustomFieldValue $value, mixed $old): void
    {
        $entity = $value->entity;
        $new = $value->getValue();

        if ($this->isEmpty($old) && $this->isEmpty($new)) {
            return;
        }

        activity((string) config('activitylog.default_log_name'))
            ->performedOn($entity)
            ->causedBy(auth()->user())
            ->withProperties([
                'custom_field_changes' => [[
                    'code' => $value->customField->code,
                    'label' => $value->customField->name,
                    'type' => $value->customField->type,
                    'old' => $this->describe($value->customField, $old),
                    'new' => $this->describe($value->customField, $new),
                ]],
            ])
            ->event('custom_field_changes')
            ->log('custom_field_changes');
    }

    /**
     * @return array{value: mixed, label: string}
     */
    private function describe(CustomField $field, mixed $value): array
    {
        if ($this->isEmpty($value)) {
            return ['value' => null, 'label' => '—'];
        }

        $dataType = CustomFieldsType::getFieldType($field->type)->dataType;

        $label = match ($dataType) {
            FieldDataType::SINGLE_CHOICE => self::optionLabel($field, $value) ?? (string) $value,
            FieldDataType::MULTI_CHOICE => $this->multiOptionLabels($field, $value),
            FieldDataType::BOOLEAN => $value ? 'Yes' : 'No',
            FieldDataType::DATE => $value instanceof Carbon ? $value->toDateString() : (string) $value,
            FieldDataType::DATE_TIME => $value instanceof Carbon ? $value->toDateTimeString() : (string) $value,
            default => (string) $value,
        };

        return ['value' => $value, 'label' => $label];
    }

    private static function optionLabel(CustomField $field, mixed $value): ?string
    {
        return $field->options->first(fn (CustomFieldOption $option): bool => (string) $option->getKey() === (string) $value)?->name;
    }

    private function multiOptionLabels(CustomField $field, mixed $value): string
    {
        $ids = is_iterable($value) ? collect($value) : collect();

        $labels = $ids
            ->map(fn (mixed $id): ?string => self::optionLabel($field, $id))
            ->filter()
            ->all();

        return $labels === [] ? (string) json_encode($value) : implode(', ', $labels);
    }

    private function isEmpty(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (is_iterable($value)) {
            return collect($value)->isEmpty();
        }

        return false;
    }
}

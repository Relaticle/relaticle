<?php

declare(strict_types=1);

namespace Relaticle\ActivityLog\Filament\Schemas;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Relaticle\ActivityLog\Models\Activity;

final class AttributeHistory extends Component
{
    #[Locked]
    public string $subjectType;

    #[Locked]
    public string $subjectId;

    #[Locked]
    public string $attribute;

    /** @return list<array{old_value: string, new_value: string, causer_name: string, created_at: string, created_at_human: string}> */
    #[Computed]
    public function historyData(): array
    {
        $activities = Activity::query()
            ->where('subject_type', $this->subjectType)
            ->where('subject_id', $this->subjectId)
            ->where('event', 'updated')
            ->with('causer')
            ->latest()
            ->limit(50)
            ->get();

        $history = [];

        foreach ($activities as $activity) {
            $causerName = $this->resolveCauserName($activity);

            if ($this->addNativeAttributeHistory($activity, $history, $causerName)) {
                continue;
            }

            $this->addCustomFieldHistory($activity, $history, $causerName);
        }

        return $history;
    }

    /**
     * @param  list<array{old_value: string, new_value: string, causer_name: string, created_at: string, created_at_human: string}>  $history
     */
    private function addNativeAttributeHistory(Activity $activity, array &$history, string $causerName): bool
    {
        $changes = $activity->attribute_changes;

        if (! $changes) {
            return false;
        }

        $attributes = $changes['attributes'] ?? [];
        $attributes = $attributes instanceof Collection ? $attributes->toArray() : (array) $attributes;

        if (! array_key_exists($this->attribute, $attributes)) {
            return false;
        }

        $old = $changes['old'] ?? [];
        $old = $old instanceof Collection ? $old->toArray() : (array) $old;

        $history[] = [
            'old_value' => (string) ($old[$this->attribute] ?? '(empty)'),
            'new_value' => (string) ($attributes[$this->attribute] ?? '(empty)'),
            'causer_name' => $causerName,
            'created_at' => $activity->created_at->toIso8601String(),
            'created_at_human' => $activity->created_at->diffForHumans(),
        ];

        return true;
    }

    /**
     * @param  list<array{old_value: string, new_value: string, causer_name: string, created_at: string, created_at_human: string}>  $history
     */
    private function addCustomFieldHistory(Activity $activity, array &$history, string $causerName): void
    {
        /** @var list<array{code: string, label: string, old: array{value: mixed, label: mixed}, new: array{value: mixed, label: mixed}}> $cfChanges */
        $cfChanges = $activity->properties['custom_field_changes'] ?? [];
        $match = collect($cfChanges)->first(fn (array $cf): bool => $cf['code'] === $this->attribute);

        if (! $match) {
            return;
        }

        $history[] = [
            'old_value' => $this->formatCustomFieldValue($match['old']),
            'new_value' => $this->formatCustomFieldValue($match['new']),
            'causer_name' => $causerName,
            'created_at' => $activity->created_at->toIso8601String(),
            'created_at_human' => $activity->created_at->diffForHumans(),
        ];
    }

    /**
     * @param  array{value: mixed, label: mixed}  $displayValue
     */
    private function formatCustomFieldValue(array $displayValue): string
    {
        $label = $displayValue['label'] ?? null;
        $value = $displayValue['value'] ?? null;

        if ($label !== null) {
            return is_array($label) ? implode(', ', $label) : (string) $label;
        }

        if ($value === null) {
            return '(empty)';
        }

        return is_array($value) ? implode(', ', $value) : (string) $value;
    }

    private function resolveCauserName(Activity $activity): string
    {
        $causer = $activity->causer;

        if (! $causer) {
            return 'System';
        }

        if (method_exists($causer, 'getFilamentName')) {
            return $causer->getFilamentName();
        }

        return $causer->name ?? 'System';
    }

    public function render(): View
    {
        return view('activity-log::filament.schemas.attribute-history', [
            'history' => $this->historyData(),
            'fieldLabel' => str($this->attribute)
                ->when(str($this->attribute)->endsWith('_id'), fn (Stringable $s) => $s->beforeLast('_id'))
                ->headline()
                ->toString(),
        ]);
    }
}

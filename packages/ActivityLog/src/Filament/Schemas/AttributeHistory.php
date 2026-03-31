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
            ->get();

        $history = [];

        foreach ($activities as $activity) {
            $changes = $activity->attribute_changes;

            if (! $changes) {
                continue;
            }

            $attributes = $changes['attributes'] ?? [];
            $attributes = $attributes instanceof Collection ? $attributes->toArray() : (array) $attributes;

            $old = $changes['old'] ?? [];
            $old = $old instanceof Collection ? $old->toArray() : (array) $old;

            if (! array_key_exists($this->attribute, $attributes)) {
                continue;
            }

            $causer = $activity->causer;
            $causerName = 'System';

            if ($causer) {
                $causerName = method_exists($causer, 'getFilamentName')
                    ? $causer->getFilamentName()
                    : ($causer->name ?? 'System');
            }

            $history[] = [
                'old_value' => (string) ($old[$this->attribute] ?? '(empty)'),
                'new_value' => (string) ($attributes[$this->attribute] ?? '(empty)'),
                'causer_name' => $causerName,
                'created_at' => $activity->created_at->toIso8601String(),
                'created_at_human' => $activity->created_at->diffForHumans(),
            ];
        }

        return $history;
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

<?php

declare(strict_types=1);

namespace Relaticle\ActivityLog\Filament\Schemas;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Stringable;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Relaticle\ActivityLog\Models\Activity;

final class ActivityTimeline extends Component
{
    #[Locked]
    public string $subjectType;

    #[Locked]
    public string $subjectId;

    public int $page = 1;

    public string $filter = 'all';

    #[Locked]
    public int $perPage = 20;

    public function mount(string $subjectType, string $subjectId): void
    {
        $this->subjectType = $subjectType;
        $this->subjectId = $subjectId;
    }

    /** @return array{groups: list<array{label: string, date: string, entries: list<array<string, mixed>>}>, hasMore: bool} */
    #[Computed]
    public function timelineData(): array
    {
        $query = Activity::query()
            ->where('subject_type', $this->subjectType)
            ->where('subject_id', $this->subjectId)
            ->with('causer')
            ->latest();

        if ($this->filter !== 'all') {
            $query->where('event', $this->filter);
        }

        $activities = $query->take(($this->page * $this->perPage) + 1)->get();
        $hasMore = $activities->count() > ($this->page * $this->perPage);
        $activities = $activities->take($this->page * $this->perPage);

        $grouped = $activities->groupBy(
            fn (Activity $activity): string => $activity->created_at->toDateString()
        );

        $groups = [];
        foreach ($grouped as $date => $dateActivities) {
            $entries = [];
            foreach ($dateActivities as $activity) {
                $entries[] = $this->transformActivity($activity);
            }

            $groups[] = [
                'label' => $this->humanizeDateLabel($date),
                'date' => $date,
                'entries' => $entries,
            ];
        }

        return [
            'groups' => $groups,
            'hasMore' => $hasMore,
        ];
    }

    public function loadMore(): void
    {
        $this->page++;
        unset($this->timelineData);
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->page = 1;
        unset($this->timelineData);
    }

    public function render(): View
    {
        return view('activity-log::filament.schemas.activity-timeline', [
            'data' => $this->timelineData(),
        ]);
    }

    /** @return array<string, mixed> */
    private function transformActivity(Activity $activity): array
    {
        return [
            'id' => $activity->id,
            'event' => $activity->event,
            'description' => $this->buildDescription($activity),
            'causer_name' => $this->resolveCauserName($activity),
            'causer_avatar' => $activity->causer?->getAttribute('profile_photo_url'),
            'causer_initials' => $this->resolveCauserInitials($activity),
            'changes' => $activity->event === 'updated' ? $this->formatChanges($activity) : null,
            'field_count' => $activity->event === 'updated' ? $this->countChangedFields($activity) : 0,
            'created_at' => $activity->created_at->toIso8601String(),
            'created_at_human' => $activity->created_at->diffForHumans(),
            'created_at_time' => $activity->created_at->format('g:i A'),
        ];
    }

    private function humanizeDateLabel(string $date): string
    {
        $carbon = Date::parse($date);

        if ($carbon->isToday()) {
            return 'Today';
        }

        if ($carbon->isYesterday()) {
            return 'Yesterday';
        }

        if ($carbon->isCurrentWeek()) {
            return $carbon->format('l');
        }

        if ($carbon->isCurrentYear()) {
            return $carbon->format('M j');
        }

        return $carbon->format('M j, Y');
    }

    private function resolveCauserInitials(Activity $activity): ?string
    {
        $name = $this->resolveCauserName($activity);

        if (! $name) {
            return null;
        }

        $parts = explode(' ', $name);

        if (count($parts) >= 2) {
            return mb_strtoupper(mb_substr($parts[0], 0, 1).mb_substr(end($parts), 0, 1));
        }

        return mb_strtoupper(mb_substr($name, 0, 2));
    }

    private function countChangedFields(Activity $activity): int
    {
        $changes = $activity->attribute_changes;

        if (! $changes) {
            return 0;
        }

        $attributes = $changes['attributes'] ?? [];
        $attributes = $attributes instanceof Collection ? $attributes->toArray() : (array) $attributes;

        return count($attributes);
    }

    private function buildDescription(Activity $activity): string
    {
        $causerName = $this->resolveCauserName($activity) ?? 'System';
        $modelLabel = ucfirst($this->subjectType);

        return match ($activity->event) {
            'created' => "{$causerName} created this {$modelLabel}",
            'updated' => $this->buildUpdatedDescription($activity, $causerName),
            'deleted' => "{$causerName} deleted this {$modelLabel}",
            'restored' => "{$causerName} restored this {$modelLabel}",
            default => "{$causerName} {$activity->event}",
        };
    }

    private function buildUpdatedDescription(Activity $activity, string $causerName): string
    {
        $changes = $activity->attribute_changes;

        $attributes = $changes['attributes'] ?? [];
        $attributes = $attributes instanceof Collection ? $attributes->toArray() : (array) $attributes;

        if (blank($attributes)) {
            return "{$causerName} updated this record";
        }

        $changedFields = array_keys($attributes);

        $fieldList = collect($changedFields)
            ->map(fn (string $field): string => $this->humanizeFieldName($field))
            ->join(', ', ' and ');

        return "{$causerName} updated {$fieldList}";
    }

    /** @return array{old: array<string, string>, attributes: array<string, string>}|null */
    private function formatChanges(Activity $activity): ?array
    {
        $changes = $activity->attribute_changes;

        if (! $changes) {
            return null;
        }

        $old = $changes['old'] ?? [];
        $attributes = $changes['attributes'] ?? [];

        $old = $old instanceof Collection ? $old->toArray() : (array) $old;
        $attributes = $attributes instanceof Collection ? $attributes->toArray() : (array) $attributes;

        return [
            'old' => array_map($this->formatValue(...), $old),
            'attributes' => array_map($this->formatValue(...), $attributes),
        ];
    }

    private function resolveCauserName(Activity $activity): ?string
    {
        $causer = $activity->causer;

        if (! $causer) {
            return null;
        }

        if (method_exists($causer, 'getFilamentName')) {
            return $causer->getFilamentName();
        }

        return $causer->name ?? null;
    }

    private function humanizeFieldName(string $field): string
    {
        return str($field)->when(
            str($field)->endsWith('_id'),
            fn (Stringable $s) => $s->beforeLast('_id'),
        )->headline()->toString();
    }

    private function formatValue(mixed $value): string
    {
        if (is_null($value)) {
            return '(empty)';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return (string) json_encode($value, JSON_PRETTY_PRINT);
        }

        $stringValue = (string) $value;

        if (preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}/', $stringValue)) {
            try {
                return Date::parse($stringValue)->format('M j, Y g:i A');
            } catch (\Exception) {
                // Fall through
            }
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $stringValue)) {
            try {
                return Date::parse($stringValue)->format('M j, Y');
            } catch (\Exception) {
                // Fall through
            }
        }

        return $stringValue;
    }
}

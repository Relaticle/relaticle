<?php

declare(strict_types=1);

namespace Relaticle\ActivityLog\Filament\Schemas;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Relaticle\ActivityLog\Models\Activity;

final class ActivityTimeline extends Component
{
    public string $subjectType;

    public string $subjectId;

    public string $teamId;

    public int $page = 1;

    public string $filter = 'all';

    public int $perPage = 20;

    public function mount(string $subjectType, string $subjectId, string $teamId): void
    {
        $this->subjectType = $subjectType;
        $this->subjectId = $subjectId;
        $this->teamId = $teamId;
    }

    /** @return array{entries: list<array<string, mixed>>, hasMore: bool} */
    #[Computed]
    public function timelineData(): array
    {
        $query = Activity::query()->withoutGlobalScopes()
            ->where('subject_type', $this->subjectType)
            ->where('subject_id', $this->subjectId)
            ->where('team_id', $this->teamId)
            ->with('causer')
            ->latest();

        if ($this->filter !== 'all') {
            $query->where('event', $this->filter);
        }

        $activities = $query->take(($this->page * $this->perPage) + 1)->get();
        $hasMore = $activities->count() > ($this->page * $this->perPage);
        $activities = $activities->take($this->page * $this->perPage);

        return [
            'entries' => $activities->map(fn (Activity $activity): array => [
                'id' => $activity->id,
                'event' => $activity->event,
                'description' => $this->buildDescription($activity),
                'causer_name' => $this->resolveCauserName($activity),
                'causer_avatar' => $activity->causer?->profile_photo_url,
                'changes' => $activity->event === 'updated' ? $this->formatChanges($activity) : null,
                'created_at' => $activity->created_at->toIso8601String(),
                'created_at_human' => $activity->created_at->diffForHumans(),
            ])->values()->toArray(),
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
            'data' => $this->timelineData,
        ]);
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
            fn ($s) => $s->beforeLast('_id'),
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
            return json_encode($value, JSON_PRETTY_PRINT);
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

<?php

declare(strict_types=1);

namespace Relaticle\Chat\Services;

use App\Filament\Resources\TaskResource;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Relaticle\Chat\Data\MyTaskItem;

final readonly class MyTasksService
{
    private const int MAX_ITEMS = 5;

    /**
     * @return Collection<int, MyTaskItem>
     */
    public function forUser(User $user, Team $team): Collection
    {
        $startOfToday = Date::now()->startOfDay();

        $dueFieldId = DB::table('custom_fields')
            ->where('tenant_id', $team->getKey())
            ->where('entity_type', 'task')
            ->where('code', 'due_date')
            ->value('id');

        $statusFieldId = DB::table('custom_fields')
            ->where('tenant_id', $team->getKey())
            ->where('entity_type', 'task')
            ->where('code', 'status')
            ->value('id');

        $doneOptionId = $statusFieldId === null ? null : DB::table('custom_field_options')
            ->where('custom_field_id', $statusFieldId)
            ->where('name', 'Done')
            ->value('id');

        $query = DB::table('tasks as t')
            ->join('task_user as tu', 'tu.task_id', '=', 't.id')
            ->where('t.team_id', $team->getKey())
            ->where('tu.user_id', $user->getKey())
            ->whereNull('t.deleted_at')
            ->when($doneOptionId !== null, function ($query) use ($statusFieldId, $doneOptionId): void {
                $query->whereNotExists(function ($sub) use ($statusFieldId, $doneOptionId): void {
                    $sub->select(DB::raw(1))
                        ->from('custom_field_values as st')
                        ->whereColumn('st.entity_id', 't.id')
                        ->where('st.entity_type', 'task')
                        ->where('st.custom_field_id', $statusFieldId)
                        ->where('st.string_value', $doneOptionId);
                });
            });

        if ($dueFieldId !== null) {
            $query
                ->select(['t.id', 't.title', 't.created_at', 'due.datetime_value as due_at'])
                ->leftJoin('custom_field_values as due', function (JoinClause $join) use ($dueFieldId): void {
                    $join->on('due.entity_id', '=', 't.id')
                        ->where('due.entity_type', '=', 'task')
                        ->where('due.custom_field_id', '=', $dueFieldId);
                })
                ->orderByRaw('due.datetime_value ASC NULLS LAST');
        } else {
            $query
                ->select(['t.id', 't.title', 't.created_at'])
                ->selectRaw('NULL as due_at');
        }

        $rows = $query
            ->latest('t.created_at')
            ->limit(self::MAX_ITEMS)
            ->get();

        $tasksUrl = TaskResource::getUrl('index', [
            'tenant' => $team,
            'tableFilters' => ['assigned_to_me' => ['isActive' => true]],
        ]);

        return $rows->map(function (object $row) use ($tasksUrl, $startOfToday): MyTaskItem {
            $dueAt = $row->due_at !== null ? Date::parse($row->due_at) : null;

            return new MyTaskItem(
                id: (string) $row->id,
                title: (string) $row->title,
                dueAt: $dueAt,
                severity: $dueAt instanceof Carbon ? $this->severity($dueAt, $startOfToday) : null,
                editUrl: $tasksUrl.'#task-'.$row->id,
            );
        })->values();
    }

    private function severity(Carbon $dueAt, Carbon $startOfToday): string
    {
        if ($dueAt->lt($startOfToday)) {
            return 'overdue';
        }

        if ($dueAt->lt($startOfToday->copy()->endOfDay())) {
            return 'today';
        }

        return 'tomorrow';
    }
}

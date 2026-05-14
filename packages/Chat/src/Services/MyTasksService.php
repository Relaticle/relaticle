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
        $endOfTomorrow = Date::now()->addDay()->endOfDay();

        $dueFieldId = DB::table('custom_fields')
            ->where('tenant_id', $team->getKey())
            ->where('entity_type', 'task')
            ->where('code', 'due_date')
            ->value('id');

        if ($dueFieldId === null) {
            return new Collection;
        }

        $statusFieldId = DB::table('custom_fields')
            ->where('tenant_id', $team->getKey())
            ->where('entity_type', 'task')
            ->where('code', 'status')
            ->value('id');

        $doneOptionId = $statusFieldId === null ? null : DB::table('custom_field_options')
            ->where('custom_field_id', $statusFieldId)
            ->where('name', 'Done')
            ->value('id');

        $rows = DB::table('tasks as t')
            ->select(['t.id', 't.title', 'due.datetime_value as due_at'])
            ->join('task_user as tu', 'tu.task_id', '=', 't.id')
            ->join('custom_field_values as due', function (JoinClause $join) use ($dueFieldId): void {
                $join->on('due.entity_id', '=', 't.id')
                    ->where('due.entity_type', '=', 'task')
                    ->where('due.custom_field_id', '=', $dueFieldId);
            })
            ->where('t.team_id', $team->getKey())
            ->where('tu.user_id', $user->getKey())
            ->whereNull('t.deleted_at')
            ->where('due.datetime_value', '<=', $endOfTomorrow)
            ->when($doneOptionId !== null, function ($query) use ($statusFieldId, $doneOptionId): void {
                $query->whereNotExists(function ($sub) use ($statusFieldId, $doneOptionId): void {
                    $sub->select(DB::raw(1))
                        ->from('custom_field_values as st')
                        ->whereColumn('st.entity_id', 't.id')
                        ->where('st.entity_type', 'task')
                        ->where('st.custom_field_id', $statusFieldId)
                        ->where('st.string_value', $doneOptionId);
                });
            })
            ->orderBy('due.datetime_value', 'asc')
            ->limit(self::MAX_ITEMS)
            ->get();

        return $rows->map(function (object $row) use ($team, $startOfToday): MyTaskItem {
            $dueAt = Date::parse($row->due_at);

            return new MyTaskItem(
                id: (string) $row->id,
                title: (string) $row->title,
                dueAt: $dueAt,
                severity: $this->severity($dueAt, $startOfToday),
                editUrl: TaskResource::getUrl('index', ['tenant' => $team]).'?tableFilters[assigned_to_me][isActive]=true#task-'.$row->id,
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

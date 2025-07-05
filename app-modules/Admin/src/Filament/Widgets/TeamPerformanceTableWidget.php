<?php

declare(strict_types=1);

namespace Relaticle\Admin\Filament\Widgets;

use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Relaticle\Admin\Filament\Widgets\Concerns\HasCustomFieldQueries;

final class TeamPerformanceTableWidget extends BaseWidget
{
    use HasCustomFieldQueries;

    protected static ?string $heading = '👥 Team Performance Analytics';

    private static ?string $description = 'Individual user productivity and activity metrics across all tenants';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'md' => 'full',
        'lg' => 2,
        'xl' => 2,
        '2xl' => 2,
    ];

    private static string $color = 'primary';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('👤 Team Member')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-user'),

                Tables\Columns\TextColumn::make('tasks_created')
                    ->label('📝 Tasks')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('tasks_completed')
                    ->label('✅ Completed')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('completion_rate')
                    ->label('📊 Success Rate')
                    ->formatStateUsing(fn ($state): string => $state.'%')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state >= 80 => 'success',
                        $state >= 60 => 'info',
                        $state >= 40 => 'warning',
                        default => 'danger'
                    })
                    ->icon(fn ($state): string => match (true) {
                        $state >= 80 => 'heroicon-o-trophy',
                        $state >= 60 => 'heroicon-o-check-circle',
                        $state >= 40 => 'heroicon-o-clock',
                        default => 'heroicon-o-exclamation-triangle'
                    })
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('opportunities_created')
                    ->label('💼 Opportunities')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('companies_created')
                    ->label('🏢 Companies')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('last_activity')
                    ->label('🕐 Last Activity')
                    ->dateTime('M j, Y g:i A')
                    ->since()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('completion_rate', 'desc')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->striped()
            ->emptyStateHeading('No Team Activity')
            ->emptyStateDescription('User performance data will appear here once team members start creating tasks, opportunities, and companies')
            ->emptyStateIcon('heroicon-o-users');
    }

    protected function getTableQuery(): Builder
    {
        $completionOptionIds = $this->getCompletionStatusOptionIds('task', 'status');
        $completionOptionIdsStr = implode(',', $completionOptionIds);

        return User::query()
            ->select([
                'users.id',
                'users.name',
                'users.created_at',
                DB::raw('(SELECT COUNT(*) FROM tasks WHERE tasks.creator_id = users.id AND tasks.deleted_at IS NULL AND tasks.creation_source != "system") as tasks_created'),
                DB::raw("(
                    SELECT COUNT(*) 
                    FROM tasks 
                    LEFT JOIN custom_field_values cfv ON tasks.id = cfv.entity_id AND cfv.entity_type = 'task'
                    LEFT JOIN custom_fields cf ON cfv.custom_field_id = cf.id AND cf.code = 'status'
                    WHERE tasks.creator_id = users.id 
                    AND tasks.deleted_at IS NULL 
                    AND tasks.creation_source != 'system'
                    AND cfv.integer_value IN ({$completionOptionIdsStr})
                ) as tasks_completed"),
                DB::raw("(
                    CASE 
                        WHEN (SELECT COUNT(*) FROM tasks WHERE tasks.creator_id = users.id AND tasks.deleted_at IS NULL AND tasks.creation_source != 'system') > 0 
                        THEN ROUND(
                            (
                                (SELECT COUNT(*) 
                                FROM tasks 
                                LEFT JOIN custom_field_values cfv ON tasks.id = cfv.entity_id AND cfv.entity_type = 'task'
                                LEFT JOIN custom_fields cf ON cfv.custom_field_id = cf.id AND cf.code = 'status'
                                WHERE tasks.creator_id = users.id 
                                AND tasks.deleted_at IS NULL 
                                AND tasks.creation_source != 'system'
                                AND cfv.integer_value IN ({$completionOptionIdsStr})) 
                                / 
                                (SELECT COUNT(*) FROM tasks WHERE tasks.creator_id = users.id AND tasks.deleted_at IS NULL AND tasks.creation_source != 'system')
                            ) * 100
                        )
                        ELSE 0 
                    END
                ) as completion_rate"),
                DB::raw('(SELECT COUNT(*) FROM opportunities WHERE opportunities.creator_id = users.id AND opportunities.deleted_at IS NULL AND opportunities.creation_source != "system") as opportunities_created'),
                DB::raw('(SELECT COUNT(*) FROM companies WHERE companies.creator_id = users.id AND companies.deleted_at IS NULL AND companies.creation_source != "system") as companies_created'),
                DB::raw('GREATEST(
                    COALESCE((SELECT MAX(created_at) FROM tasks WHERE creator_id = users.id AND creation_source != "system"), "1970-01-01"),
                    COALESCE((SELECT MAX(created_at) FROM opportunities WHERE creator_id = users.id AND creation_source != "system"), "1970-01-01"),
                    COALESCE((SELECT MAX(created_at) FROM companies WHERE creator_id = users.id AND creation_source != "system"), "1970-01-01"),
                    COALESCE((SELECT MAX(created_at) FROM notes WHERE creator_id = users.id AND creation_source != "system"), "1970-01-01")
                ) as last_activity'),
            ])
            ->havingRaw('tasks_created > 0 OR opportunities_created > 0 OR companies_created > 0');
    }
}

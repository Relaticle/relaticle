<?php

declare(strict_types=1);

namespace Relaticle\ActivityLog\Filament\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Relaticle\ActivityLog\Filament\Schemas\ActivityTimeline;
use Relaticle\ActivityLog\Models\Activity;

final class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activitiesAsSubject';

    protected static ?string $title = 'Activity';

    public static function getTabComponent(Model $ownerRecord, string $pageClass): Tab
    {
        return Tab::make('Activity')
            ->badge(fn (): int => Activity::withoutGlobalScopes()
                ->where('subject_type', $ownerRecord->getMorphClass())
                ->where('subject_id', $ownerRecord->getKey())
                ->where('team_id', $ownerRecord->team_id)
                ->count()
            )
            ->icon(Heroicon::OutlinedClock);
    }

    public function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->columns([]);
    }

    public function content(Schema $schema): Schema
    {
        $record = $this->getOwnerRecord();

        return $schema
            ->components([
                Livewire::make(ActivityTimeline::class, [
                    'subjectType' => $record->getMorphClass(),
                    'subjectId' => $record->getKey(),
                    'teamId' => $record->team_id,
                ])->lazy(),
            ]);
    }
}

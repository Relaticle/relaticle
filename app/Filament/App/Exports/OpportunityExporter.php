<?php

declare(strict_types=1);

namespace App\Filament\App\Exports;

use App\Models\Opportunity;
use App\Models\Team;
use Carbon\Carbon;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Relaticle\CustomFields\Filament\Exports\CustomFieldsExporter;

final class OpportunityExporter extends Exporter
{
    protected static ?string $model = Opportunity::class;

    /**
     * @param  array<string, string>  $columnMap
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        Export $export,
        array $columnMap,
        array $options,
    ) {
        parent::__construct($export, $columnMap, $options);

        // Set the team_id on the export record
        if (Auth::check() && Auth::user()->currentTeam) {
            /** @var Team $currentTeam */
            $currentTeam = Auth::user()->currentTeam;
            $export->team_id = $currentTeam->getKey();
        }
    }

    /**
     * Make exports tenant-aware by scoping to the current team
     */
    public static function modifyQuery(Builder $query): Builder
    {
        if (Auth::check() && Auth::user()->currentTeam) {
            /** @var Team $currentTeam */
            $currentTeam = Auth::user()->currentTeam;

            return $query->where('team_id', $currentTeam->getKey());
        }

        return $query;
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('name')
                ->label('Opportunity Name'),
            ExportColumn::make('company.name')
                ->label('Company'),
            ExportColumn::make('contact.name')
                ->label('Contact Person'),
            ExportColumn::make('team.name')
                ->label('Team'),
            ExportColumn::make('creator.name')
                ->label('Created By'),
            ExportColumn::make('notes_count')
                ->label('Number of Notes')
                ->state(fn (Opportunity $opportunity): int => $opportunity->notes()->count()),
            ExportColumn::make('tasks_count')
                ->label('Number of Tasks')
                ->state(fn (Opportunity $opportunity): int => $opportunity->tasks()->count()),
            ExportColumn::make('created_at')
                ->label('Created At')
                ->formatStateUsing(fn (Carbon $state): string => $state->format('Y-m-d H:i:s')),
            ExportColumn::make('updated_at')
                ->label('Updated At')
                ->formatStateUsing(fn (Carbon $state): string => $state->format('Y-m-d H:i:s')),
            ExportColumn::make('creation_source')
                ->label('Creation Source')
                ->formatStateUsing(fn ($state): string => $state->value ?? (string) $state),

            // Add all custom fields automatically
            ...CustomFieldsExporter::getColumns(self::getModel()),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your opportunity export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if (($failedRowsCount = $export->getFailedRowsCount()) !== 0) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}

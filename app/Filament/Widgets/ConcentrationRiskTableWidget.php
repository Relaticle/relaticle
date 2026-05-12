<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\RiskBand;
use App\Models\Company;
use App\Support\CountryFlag;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

final class ConcentrationRiskTableWidget extends TableWidget
{
    protected static ?int $sort = 5;

    /** @return array<string, int|string> */
    public function getColumnSpan(): array
    {
        return ['default' => 'full', 'lg' => 1];
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Top Concentration Risk')
            ->description('Highest-concentration accounts — review with AI.')
            ->query(
                Company::query()
                    ->whereNotNull('concentration_percentage')
                    ->orderByDesc('concentration_percentage')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Account')
                    ->searchable()
                    ->limit(22),
                TextColumn::make('concentration_percentage')
                    ->label('Concentration')
                    ->formatStateUsing(fn (?string $state): string => $state !== null ? number_format((float) $state, 1).'%' : '—')
                    ->badge()
                    ->color(fn (Company $record): string => match ($record->portfolio->riskBand()) {
                        RiskBand::Low => 'success',
                        RiskBand::Medium => 'warning',
                        RiskBand::High => 'danger',
                    })
                    ->sortable(),
                TextColumn::make('partner_source')
                    ->label('Source')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('geography')
                    ->label('Geo')
                    ->formatStateUsing(fn (?string $state): string => $state !== null
                        ? CountryFlag::emoji($state).' '.$state
                        : '—')
                    ->toggleable(),
            ])
            ->recordActions([
                Action::make('explain_risk')
                    ->label('Explain risk with AI')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->color('gray')
                    ->modalHeading(fn (Company $record): string => 'Risk Explanation — '.$record->name)
                    ->modalDescription(fn (Company $record): string => sprintf(
                        'Concentration: %s%%  ·  Risk Band: %s  ·  Source: %s  ·  Geography: %s',
                        number_format((float) ($record->concentration_percentage ?? 0), 1),
                        $record->portfolio->riskBand()->getLabel(),
                        $record->partner_source?->getLabel() ?? '—',
                        $record->geography !== null
                            ? CountryFlag::emoji($record->geography).' '.CountryFlag::name($record->geography)
                            : '—',
                    ))
                    ->modalContent(view('filament.widgets.risk-explain-placeholder'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->paginated(false);
    }
}

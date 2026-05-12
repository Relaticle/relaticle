<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\RiskBand;
use App\Models\Company;
use App\Services\Portfolio\PortfolioRiskContextService;
use App\Support\CountryFlag;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\HtmlString;

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
                    ->modalHeading(fn (Company $record): string => 'Risk Context — '.$record->name)
                    ->modalContent(fn (Company $record): HtmlString => new HtmlString(
                        view('filament.widgets.risk-explain-modal', [
                            'context' => resolve(PortfolioRiskContextService::class)->riskContext($record),
                        ])->render()
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->paginated(false);
    }
}

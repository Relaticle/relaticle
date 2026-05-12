<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\CreationSource;
use App\Enums\PartnerSource;
use App\Enums\RiskBand;
use App\Filament\Exports\CompanyExporter;
use App\Filament\Resources\CompanyResource\Pages\ListCompanies;
use App\Filament\Resources\CompanyResource\Pages\ViewCompany;
use App\Models\Company;
use App\Support\CountryFlag;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Relaticle\CustomFields\Facades\CustomFields;

final class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home-modern';

    protected static ?int $navigationSort = 2;

    protected static string|\UnitEnum|null $navigationGroup = 'Workspace';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Select::make('account_owner_id')
                    ->relationship('accountOwner', 'name')
                    ->label('Account Owner')
                    ->nullable()
                    ->preload()
                    ->searchable(),

                Section::make('Portfolio Metadata')
                    ->description('Risk and sourcing data used in portfolio analytics.')
                    ->icon('heroicon-o-chart-bar')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('partner_source')
                            ->label('Partner Source')
                            ->options(PartnerSource::class)
                            ->nullable()
                            ->searchable(),
                        Select::make('geography')
                            ->label('Geography')
                            ->options(CountryFlag::options())
                            ->nullable()
                            ->searchable(),
                        TextInput::make('concentration_percentage')
                            ->label('Concentration %')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('%')
                            ->nullable()
                            ->hint(fn (?string $state): ?string => match (true) {
                                $state === null || $state === '' => null,
                                (float) $state >= 30 => 'High Risk',
                                (float) $state >= 10 => 'Medium Risk',
                                default => 'Low Risk',
                            })
                            ->hintColor(fn (?string $state): ?string => match (true) {
                                $state === null || $state === '' => null,
                                (float) $state >= 30 => 'danger',
                                (float) $state >= 10 => 'warning',
                                default => 'success',
                            }),
                        Toggle::make('is_recurring')
                            ->label('Recurring Revenue')
                            ->inline(false),
                    ]),

                CustomFields::form()->build()->columnSpanFull()->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->view('filament.tables.columns.logo-name-column'),
                TextColumn::make('partner_source')
                    ->label('Source')
                    ->badge()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('geography')
                    ->label('Geo')
                    ->formatStateUsing(fn (?string $state): string => $state !== null
                        ? CountryFlag::emoji($state).' '.$state
                        : '—')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('concentration_percentage')
                    ->label('Concentration')
                    ->formatStateUsing(fn (?string $state): string => $state !== null
                        ? number_format((float) $state, 1).'%'
                        : '—')
                    ->badge()
                    ->color(fn (Company $record): string => match ($record->portfolio->riskBand()) {
                        RiskBand::Low => 'success',
                        RiskBand::Medium => 'warning',
                        RiskBand::High => 'danger',
                    })
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_recurring')
                    ->label('Recurring')
                    ->boolean()
                    ->trueIcon('heroicon-o-arrow-path')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('accountOwner.name')
                    ->label('Account Owner')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->getStateUsing(fn (Company $record): string => $record->created_by),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                TextColumn::make('created_at')
                    ->label('Creation Date')
                    ->dateTime()
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('Last Update')
                    ->since()
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('partner_source')
                    ->label('Partner Source')
                    ->options(PartnerSource::class)
                    ->multiple(),
                SelectFilter::make('geography')
                    ->label('Geography')
                    ->options(CountryFlag::options())
                    ->multiple()
                    ->searchable(),
                Filter::make('is_recurring')
                    ->label('Recurring Revenue Only')
                    ->query(fn (Builder $query): Builder => $query->where('is_recurring', true))
                    ->toggle(),
                SelectFilter::make('creation_source')
                    ->label('Creation Source')
                    ->options(CreationSource::class)
                    ->multiple(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    RestoreAction::make(),
                    DeleteAction::make(),
                    ForceDeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(CompanyExporter::class),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanies::route('/'),
            'view' => ViewCompany::route('/{record}'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['team', 'customFieldValues.customField.options'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

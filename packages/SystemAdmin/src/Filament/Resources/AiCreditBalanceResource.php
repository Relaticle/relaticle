<?php

declare(strict_types=1);

namespace Relaticle\SystemAdmin\Filament\Resources;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;
use Relaticle\Chat\Models\AiCreditBalance;
use Relaticle\SystemAdmin\Filament\Resources\AiCreditBalanceResource\Pages\EditAiCreditBalance;
use Relaticle\SystemAdmin\Filament\Resources\AiCreditBalanceResource\Pages\ListAiCreditBalances;
use Relaticle\SystemAdmin\Filament\Resources\AiCreditBalanceResource\Pages\ViewAiCreditBalance;

final class AiCreditBalanceResource extends Resource
{
    protected static ?string $model = AiCreditBalance::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'AI';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Credit Balance';

    protected static ?string $pluralModelLabel = 'Credit Balances';

    protected static ?string $slug = 'ai/credit-balances';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('credits_remaining')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                TextInput::make('credits_used')
                    ->numeric()
                    ->minValue(0)
                    ->disabled()
                    ->helperText('Read-only. Use the Adjust action to mutate.'),
                DateTimePicker::make('period_starts_at')
                    ->required(),
                DateTimePicker::make('period_ends_at')
                    ->required()
                    ->after('period_starts_at'),
            ]);
    }

    #[Override]
    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make([
                    TextEntry::make('team.name')->label('Team'),
                    TextEntry::make('credits_remaining')->numeric(),
                    TextEntry::make('credits_used')->numeric(),
                    TextEntry::make('period_starts_at')->dateTime(),
                    TextEntry::make('period_ends_at')->dateTime(),
                    TextEntry::make('updated_at')->dateTime(),
                ])->columnSpanFull()->columns(2),
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('credits_remaining', 'asc')
            ->columns([
                TextColumn::make('team.name')
                    ->label('Team')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('credits_remaining')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'danger',
                        $state < 50 => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('credits_used')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('period_starts_at')
                    ->date()
                    ->sortable(),
                TextColumn::make('period_ends_at')
                    ->date()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state): string => $state?->isPast() ? 'danger' : 'gray'),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('low_balance')
                    ->label('Low balance (< 50)')
                    ->query(fn (Builder $query): Builder => $query->where('credits_remaining', '<', 50)),
                Filter::make('period_expired')
                    ->label('Period expired')
                    ->query(fn (Builder $query): Builder => $query->where('period_ends_at', '<', now())),
                SelectFilter::make('team')
                    ->relationship('team', 'name')
                    ->searchable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListAiCreditBalances::route('/'),
            'view' => ViewAiCreditBalance::route('/{record}'),
            'edit' => EditAiCreditBalance::route('/{record}/edit'),
        ];
    }
}

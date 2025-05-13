<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Enums\CreationSource;
use App\Filament\App\Exports\OpportunityExporter;
use App\Filament\App\Resources\OpportunityResource\Forms\OpportunityForm;
use App\Filament\App\Resources\OpportunityResource\Pages;
use App\Filament\App\Resources\OpportunityResource\RelationManagers;
use App\Models\Opportunity;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

final class OpportunityResource extends Resource
{
    protected static ?string $model = Opportunity::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationGroup = 'Workspace';

    public static function form(Form $form): Form
    {
        return OpportunityForm::get($form);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->getStateUsing(fn (Opportunity $record): string => $record->created_by)
                    ->color(fn (Opportunity $record): string => $record->isSystemCreated() ? 'secondary' : 'primary'),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('creation_source')
                    ->label('Creation Source')
                    ->options(CreationSource::class)
                    ->multiple(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ]),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(OpportunityExporter::class),
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TasksRelationManager::class,
            RelationManagers\NotesRelationManager::class,
        ];
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOpportunities::route('/'),
            'view' => Pages\ViewOpportunity::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

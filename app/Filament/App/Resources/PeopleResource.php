<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Enums\CreationSource;
use App\Filament\App\Exports\PeopleExporter;
use App\Filament\App\Resources\PeopleResource\Pages\ListPeople;
use App\Filament\App\Resources\PeopleResource\Pages\ViewPeople;
use App\Filament\App\Resources\PeopleResource\RelationManagers\NotesRelationManager;
use App\Filament\App\Resources\PeopleResource\RelationManagers\TasksRelationManager;
use App\Models\Company;
use App\Models\People;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent;

final class PeopleResource extends Resource
{
    protected static ?string $model = People::class;

    protected static ?string $modelLabel = 'person';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationGroup = 'Workspace';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make()->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(7),
                    Forms\Components\Select::make('company_id')
                        ->relationship('company', 'name')
                        ->suffixAction(
                            Forms\Components\Actions\Action::make('Create Company')
                                ->form([
                                    TextInput::make('name')
                                        ->required(),
                                    Select::make('account_owner_id')
                                        ->relationship('accountOwner', 'name')
                                        ->label('Account Owner')
                                        ->preload()
                                        ->searchable(),
                                    CustomFieldsComponent::make()->columns(1),
                                ])
                                ->icon('heroicon-o-plus')
                                ->action(function (array $data, Forms\Set $set): void {
                                    $company = Company::create($data);
                                    $set('company_id', $company->id);
                                })
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpan(5),
                ])
                    ->columns(12),
                CustomFieldsComponent::make()
                    ->columnSpanFull()
                    ->columns(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')->label('')->size(24)->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Company')
                    ->url(fn (People $record): string => CompanyResource::getUrl('view', [$record->company_id]))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->getStateUsing(fn (People $record): string => $record->created_by)
                    ->color(fn (People $record): string => $record->isSystemCreated() ? 'secondary' : 'primary'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('creation_source')
                    ->label('Creation Source')
                    ->options(CreationSource::class)
                    ->multiple(),
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(PeopleExporter::class),
                    Tables\Actions\DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TasksRelationManager::class,
            NotesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPeople::route('/'),
            'view' => ViewPeople::route('/{record}'),
        ];
    }

    /**
     * @return Builder<People>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

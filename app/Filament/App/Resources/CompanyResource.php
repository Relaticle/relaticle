<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent;

final class CompanyResource extends Resource
{
    /**
     * The model the resource corresponds to.
     */
    protected static ?string $model = Company::class;

    /**
     * The name of the resource.
     */
    protected static ?string $slug = 'companies';

    protected static ?string $recordTitleAttribute = 'name';

    /**
     * The navigation icon for the resource.
     */
    protected static ?string $navigationIcon = 'heroicon-m-home-modern';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationGroup = 'Workspace';


    /**
     * The form schema definition for the resource.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Placeholder::make('created_at')
                    ->label('Created Date')
                    ->content(fn(?Company $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                Placeholder::make('updated_at')
                    ->label('Last Modified Date')
                    ->content(fn(?Company $record): string => $record?->updated_at?->diffForHumans() ?? '-'),

                TextInput::make('name')
                    ->required(),

                TextInput::make('address')
                    ->required(),

                TextInput::make('phone')
                    ->required(),

                CustomFieldsComponent::make()->columns(1),

            ])->columns(1)->inlineLabel();
    }

    /**
     * The table schema definition for the resource.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo')->label('')->size(30)->square(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('address'),

                TextColumn::make('phone')
            ])
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Get the pages available for the resource.
     *
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\App\Resources\CompanyResource\Pages\ListCompanies::route('/'),
            'create' => \App\Filament\App\Resources\CompanyResource\Pages\CreateCompany::route('/create'),
            'edit' => \App\Filament\App\Resources\CompanyResource\Pages\EditCompany::route('/{record}/edit'),
        ];
    }

    /**
     * Get the globally searchable attributes for the resource.
     *
     * These attributes are searchable via the global search bar.
     *
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }
}

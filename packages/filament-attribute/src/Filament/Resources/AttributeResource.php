<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Filament\Resources;

use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Illuminate\Validation\Rules\Unique;
use ManukMinasyan\FilamentAttribute\Enums\AttributeType;
use ManukMinasyan\FilamentAttribute\Filament\Forms\Components\AttributeResource\AttributeValidationComponent;
use ManukMinasyan\FilamentAttribute\Filament\Resources\AttributeResource\Pages;
use ManukMinasyan\FilamentAttribute\Models\Attribute;
use ManukMinasyan\FilamentAttribute\Services\AttributeEntityTypeService;

final class AttributeResource extends Resource
{
    protected static ?string $model = Attribute::class;

    protected static ?string $slug = 'attributes';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('General')
                            ->schema([
                                Forms\Components\Select::make('entity_type')
                                    ->options(AttributeEntityTypeService::options())
                                    ->searchable()
                                    ->required(),
                                Forms\Components\Select::make('type')
                                    ->reactive()
                                    ->options(AttributeType::class)
                                    ->searchable()

                                    ->required(),
                                Forms\Components\TextInput::make('name')
                                    ->live(onBlur: true)
                                    ->required()
                                    ->maxLength(30)
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, ?string $old, ?string $state): void {
                                        $old ??= '';
                                        $state ??= '';

                                        if (($get('code') ?? '') !== strtolower($old)) {
                                            return;
                                        }

                                        $set('code', strtolower($state));
                                    }),
                                Forms\Components\TextInput::make('code')
                                    ->live(onBlur: true)
                                    ->required()
                                    ->alphaDash()
                                    ->unique(table: Attribute::class, column: 'code', ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Forms\Get $get) => $rule->where('entity_type', $get('entity_type')))
                                    ->validationMessages([
                                        'unique' => __('validation.custom.attributes.code.unique'),
                                    ])
                                    ->maxLength(30)
                                    ->afterStateUpdated(function (Forms\Set $set, ?string $state): void {
                                        $set('code', strtolower($state ?? ''));
                                    }),
                                Forms\Components\Select::make('options_lookup_type')
                                    ->visible(fn (Forms\Get $get): bool => in_array($get('type'), [AttributeType::SELECT->value, AttributeType::MULTISELECT->value]))
                                    ->reactive()
                                    ->options([
                                        'options' => 'Options',
                                        'lookup' => 'Lookup',
                                    ])
                                    ->afterStateHydrated(function (Forms\Components\Select $component, $state, $record): void {
                                        if (blank($state)) {
                                            $optionsLookupType = $record?->lookup_type ? 'lookup' : 'options';
                                            $component->state($optionsLookupType);
                                        }
                                    })
                                    ->dehydrated(false)
                                    ->required(),
                                Forms\Components\Select::make('lookup_type')
                                    ->visible(fn (Forms\Get $get): bool => $get('options_lookup_type') === 'lookup')
                                    ->reactive()
                                    ->options(AttributeEntityTypeService::options())
                                    ->required(),
                                Forms\Components\Repeater::make('options')
                                    ->visible(fn (Forms\Get $get): bool => $get('options_lookup_type') === 'options' && in_array($get('type'), [AttributeType::SELECT->value, AttributeType::MULTISELECT->value]))
                                    ->relationship()
                                    ->simple(
                                        Forms\Components\TextInput::make('name')
                                            ->columnSpanFull()
                                            ->required(),
                                    )
                                    ->columns(2)
                                    ->label('Select Options')
                                    ->helperText('Add options for the select field.')
                                    ->defaultItems(1)
                                    ->addActionLabel('Add Option')
                                    ->reorderable()
                                    ->orderColumn('sort_order')
                                    ->columnSpanFull(),
                            ]),
                        Forms\Components\Tabs\Tab::make('Validation')
                            ->schema([
                                AttributeValidationComponent::make(),
                            ]),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('entity_type')
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ])->iconButton(),

            ])
            ->paginated(false)
            ->reorderable('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttributes::route('/'),
            'create' => Pages\CreateAttribute::route('/create'),
            'edit' => Pages\EditAttribute::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }
}

<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Filament\Forms\Components\AttributeResource;

use Filament\Forms;
use Filament\Forms\Components\Component;
use Filament\Forms\Get;
use Filament\Forms\Set;
use ManukMinasyan\FilamentAttribute\Enums\AttributeValidationRuleEnum;

final class AttributeValidationComponent extends Component
{
    protected string $view = 'filament-forms::components.group';

    public function __construct()
    {
        $this->schema([
            $this->buildValidationRulesRepeater(),
        ]);

        $this->columnSpanFull();
    }

    public static function make(): self
    {
        return app(self::class);
    }

    private function buildValidationRulesRepeater(): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make('validation_rules')
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Select::make('name')
                            ->label('Rule')
                            ->options(function (Get $get) {
                                $existingRules = $get('../../validation_rules') ?? [];

                                return collect(AttributeValidationRuleEnum::cases())
                                    ->reject(function ($enum) use ($existingRules) {
                                        return $this->hasDuplicateRule($existingRules, $enum->value);
                                    })
                                    ->mapWithKeys(fn ($enum) => [$enum->value => $enum->getLabel()])
                                    ->toArray();
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->required()
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state, ?string $old) {
                                if ($old !== $state) {
                                    $set('parameters', []);
                                }
                            })
                            ->columnSpan(1),
                        Forms\Components\Placeholder::make('description')
                            ->content(fn (Get $get): string => AttributeValidationRuleEnum::getDescriptionForRule($get('name')))
                            ->columnSpan(2),
                        $this->buildRuleParametersRepeater(),
                    ]),
            ])
            ->itemLabel(fn (array $state): string => AttributeValidationRuleEnum::getLabelForRule($state['name'] ?? null, $state['parameters'] ?? []))
            ->collapsible()
            ->reorderable()
            ->deletable()
            ->addable()
            ->hiddenLabel()
            ->defaultItems(0)
            ->columnSpanFull();
    }

    private function buildRuleParametersRepeater(): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make('parameters')
            ->simple(
                Forms\Components\TextInput::make('value')
                    ->label('Parameter Value')
                    ->required()
                    ->hiddenLabel()
                    ->maxLength(255),
            )
            ->columnSpanFull()
            ->visible(fn (Get $get): bool => AttributeValidationRuleEnum::hasParameterForRule($get('name')))
            ->minItems(1)
            ->maxItems(3)
            ->reorderable(false)
            ->defaultItems(1)
            ->addActionLabel('Add Parameter');
    }

    private function hasDuplicateRule(array $rules, string $newRule): bool
    {
        return collect($rules)->contains(function ($rule) use ($newRule) {
            return $rule['name'] === $newRule;
        });
    }
}

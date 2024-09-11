<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Filament\Forms\Components\AttributeResource;

use Filament\Forms;
use Filament\Forms\Components\Component;
use Filament\Forms\Get;
use Filament\Forms\Set;
use ManukMinasyan\FilamentAttribute\Enums\AttributeValidationRule;
use ManukMinasyan\FilamentAttribute\Enums\AttributeType;

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
                                $attributeType = AttributeType::tryFrom($get('../../type'));
                                $allowedRules = $attributeType instanceof AttributeType ? $attributeType->allowedValidationRules() : [];

                                return collect($allowedRules)
                                    ->reject(fn ($enum): bool => $this->hasDuplicateRule($existingRules, $enum->value))
                                    ->mapWithKeys(fn ($enum) => [$enum->value => $enum->getLabel()])
                                    ->toArray();
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->rules([
                                'required',
                            ])
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state, ?string $old): void {
                                if ($old !== $state) {
                                    $set('parameters', []);
                                }
                            })
                            ->columnSpan(1),
                        Forms\Components\Placeholder::make('description')
                            ->content(fn (Get $get): string => AttributeValidationRule::getDescriptionForRule($get('name')))
                            ->columnSpan(2),
                        $this->buildRuleParametersRepeater(),
                    ]),
            ])
            ->itemLabel(fn (array $state): string => AttributeValidationRule::getLabelForRule((string) $state['name'], $state['parameters'] ?? []))
            ->collapsible()
            ->reorderable()
            ->deletable()
            ->hintColor('danger')
            ->addable(fn (Get $get): bool => $get('type') && AttributeType::tryFrom($get('type')))
            ->hint(function (Get $get): string {
                $isTypeSelected = $get('type') && AttributeType::tryFrom($get('type'));

                return $isTypeSelected ? '' : 'To add validation rules, select an attribute type.';
            })
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
            ->visible(fn (Get $get): bool => AttributeValidationRule::hasParameterForRule($get('name')))
            ->minItems(1)
            ->maxItems(3)
            ->reorderable(false)
            ->defaultItems(1)
            ->addActionLabel('Add Parameter');
    }

    /**
     * Checks if a validation rule already exists in the array of rules.
     *
     * @param  array<string, array<string, string>>  $rules
     */
    private function hasDuplicateRule(array $rules, string $newRule): bool
    {
        return collect($rules)->contains(fn (array $rule): bool => $rule['name'] === $newRule);
    }
}

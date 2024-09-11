<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Filament\Forms\Components\CustomAttributes;

use Filament\Forms\Components\Field;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use ManukMinasyan\FilamentAttribute\Enums\AttributeType;
use ManukMinasyan\FilamentAttribute\Models\Attribute;
use RuntimeException;

final class AttributeComponentFactory
{
    /**
     * @var array<string, class-string<AttributeComponentInterface>>
     */
    private array $componentMap = [
        AttributeType::TEXT->value => TextInputComponent::class,
        AttributeType::TEXTAREA->value => TextareaAttributeComponent::class,
        AttributeType::PRICE->value => PriceComponent::class,
        AttributeType::DATE->value => DateComponent::class,
        AttributeType::DATETIME->value => DateTimeComponent::class,
        AttributeType::TOGGLE->value => ToggleComponent::class,
        AttributeType::SELECT->value => SelectComponent::class,
        AttributeType::MULTISELECT->value => MultiSelectComponent::class,
    ];

    /**
     * @var array<class-string<AttributeComponentInterface>, AttributeComponentInterface>
     */
    private array $instanceCache = [];

    public function __construct(private readonly Container $container) {}

    public function create(Attribute $attribute): Field
    {
        $attributeType = $attribute->type->value;

        if (! isset($this->componentMap[$attributeType])) {
            throw new InvalidArgumentException("No component registered for attribute type: {$attributeType}");
        }

        $componentClass = $this->componentMap[$attributeType];

        if (! isset($this->instanceCache[$componentClass])) {
            $component = $this->container->make($componentClass);

            if (! $component instanceof AttributeComponentInterface) {
                throw new RuntimeException("Component class {$componentClass} must implement AttributeComponentInterface");
            }

            $this->instanceCache[$componentClass] = $component;
        } else {
            $component = $this->instanceCache[$componentClass];
        }

        return $component->make($attribute);
    }
}

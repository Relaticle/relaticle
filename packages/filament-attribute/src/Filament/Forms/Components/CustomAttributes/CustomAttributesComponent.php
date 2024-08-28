<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Filament\Forms\Components\CustomAttributes;

use ManukMinasyan\FilamentAttribute\Models\Attribute;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Field;
use Illuminate\Support\Collection;
use ManukMinasyan\FilamentAttribute\Services\AttributeEntityTypeService;

final class CustomAttributesComponent extends Component
{
    protected string $view = 'filament-forms::components.group';

    /**
     * @param AttributeComponentFactory $componentFactory
     */
    public function __construct(private readonly AttributeComponentFactory $componentFactory) {
        $this->schema($this->generateSchema());
    }

    /**
     * @param string|null $name
     * @return static
     */
    public static function make(?string $name): static
    {
        return app(self::class, ['name' => $name]);
    }

    /**
     * @return array<int, Field>
     */
    protected function generateSchema(): array
    {
        return $this->getAttributes()
            ->map(fn (Attribute $attribute): Field => $this->componentFactory->create($attribute))
            ->toArray();
    }

    /**
     * @return Collection<int, Attribute>
     */
    protected function getAttributes(): Collection
    {
        return Attribute::query()
            ->with(['options'])
            ->forEntity(AttributeEntityTypeService::getMorphClassFromModel($this->getModel()))
            ->orderBy('sort_order')
            ->get();
    }
}

<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Filament\Resources\AttributeResource\Pages;

use ManukMinasyan\FilamentAttribute\Enums\AttributeEntityTypeEnum;
use ManukMinasyan\FilamentAttribute\Filament\Resources\AttributeResource;
use ManukMinasyan\FilamentAttribute\Models\Attribute;
use Filament\Actions\CreateAction;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use ManukMinasyan\FilamentAttribute\Services\AttributeEntityTypeService;

final class ListAttributes extends ListRecords
{
    protected static string $resource = AttributeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return AttributeEntityTypeService::options()
            ->mapWithKeys(fn ($label, $value) => [$label => Tab::make()->query(fn ($query) => $query->forEntity($value))])
            ->toArray();
    }
}

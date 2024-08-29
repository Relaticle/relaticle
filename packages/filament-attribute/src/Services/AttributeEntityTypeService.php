<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Services;

use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use ManukMinasyan\FilamentAttribute\Filament\Resources\AttributeResource;

final readonly class AttributeEntityTypeService
{
    /**
     * Get the options for attribute entity types.
     *
     * @return Collection<string, string>
     *
     * @throws InvalidArgumentException
     */
    public static function options(): Collection
    {
        return collect(Filament::getResources())
            ->filter(function (string $resource): bool {
                return $resource !== AttributeResource::class;
            })
            ->mapWithKeys(function (string $resource): array {
                $resourceInstance = app($resource);

                return [app($resourceInstance->getModel())->getMorphClass() => $resourceInstance::getBreadcrumb()];
            });
    }

    public static function getMorphClassFromModel(string $model): string
    {
        return app($model)->getMorphClass();
    }
}

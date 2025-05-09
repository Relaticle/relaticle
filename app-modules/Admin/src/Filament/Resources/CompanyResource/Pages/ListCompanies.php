<?php

declare(strict_types=1);

namespace Relaticle\Admin\Filament\Resources\CompanyResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Relaticle\Admin\Filament\Resources\CompanyResource;

final class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

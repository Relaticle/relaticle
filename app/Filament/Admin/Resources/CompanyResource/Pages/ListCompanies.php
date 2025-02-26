<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CompanyResource\Pages;

use App\Filament\Admin\Resources\CompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

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

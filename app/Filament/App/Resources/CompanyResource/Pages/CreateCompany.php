<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\CompanyResource\Pages;

use App\Filament\App\Resources\CompanyResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCompany extends CreateRecord
{
    /** @var class-string<CompanyResource> */
    protected static string $resource = CompanyResource::class;

    /**
     * Get the actions available on the resource creation header.
     */
    protected function getHeaderActions(): array
    {
        return [

        ];
    }

    public function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\PeopleResource\Pages;

use App\Filament\App\Exports\PeopleExporter;
use App\Filament\App\Resources\PeopleResource;
use Filament\Actions;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use Relaticle\CustomFields\Filament\Tables\Concerns\InteractsWithCustomFields;

final class ListPeople extends ListRecords
{
    use InteractsWithCustomFields;

    protected static string $resource = PeopleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ExportAction::make()
                ->exporter(PeopleExporter::class),
        ];
    }
}

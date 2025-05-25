<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\PeopleResource\Pages;

use App\Filament\App\Imports\PeopleImporter;
use App\Filament\App\Resources\PeopleResource;
use Filament\Actions;
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
            Actions\ImportAction::make()
                ->label('Import')
                ->importer(PeopleImporter::class)
                ->maxRows(10000)
                ->chunkSize(250)
                ->csvDelimiter(',')
                ->fileRules([
                    'mimes:csv,txt',
                    'max:10240', // 10MB max
                ])
                ->icon('heroicon-o-arrow-up-tray'),
        ];
    }
}

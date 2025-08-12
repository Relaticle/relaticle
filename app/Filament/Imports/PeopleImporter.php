<?php

declare(strict_types=1);

namespace App\Filament\Imports;

use App\Models\People;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

final class PeopleImporter extends Importer
{
    protected static ?string $model = People::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('team')
                ->requiredMapping()
                ->relationship()
                ->rules(['required']),
            ImportColumn::make('creator')
                ->relationship(),
            ImportColumn::make('creation_source')
                ->requiredMapping()
                ->rules(['required', 'max:50']),
            ImportColumn::make('company')
                ->relationship(),
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
        ];
    }

    public function resolveRecord(): People
    {
        return People::firstOrNew([
            'email' => $this->data['email'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your people import has completed and '.Number::format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if (($failedRowsCount = $import->getFailedRowsCount()) !== 0) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}

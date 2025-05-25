<?php

declare(strict_types=1);

namespace App\Filament\App\Imports;

use App\Models\People;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Auth;
use Relaticle\CustomFields\Filament\Imports\CustomFieldsImporter;

final class PeopleImporter extends Importer
{
    protected static ?string $model = People::class;

    /**
     * @param  array<string, string>  $columnMap
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        Import $import,
        array $columnMap,
        array $options,
    ) {
        parent::__construct($import, $columnMap, $options);

        $import->team_id = Auth::user()->currentTeam?->getKey() ?? null;
    }

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('John Doe'),

            ImportColumn::make('company_name')
                ->label('Company Name')
                ->rules(['nullable', 'string', 'max:255'])
                ->example('Acme Corporation'),

            // Add all custom fields automatically
            ...app(CustomFieldsImporter::class)->getColumns(self::getModel()),
        ];
    }

    public function resolveRecord(): People
    {
        $teamId = $this->import->team_id;

        // Get original data to access custom fields
        $originalData = $this->getOriginalData();

        // Try to find existing person by name within the same team
        $query = People::query()->where('name', $this->data['name']);

        if ($teamId) {
            $query->where('team_id', $teamId);
        }

        // First, try to find by exact name match
        $existingPerson = $query->first();

        // If no exact match found, try to find by email if provided
        if (! $existingPerson && ! empty($originalData['custom_fields_emails'])) {
            $emails = is_string($originalData['custom_fields_emails'])
                ? explode(',', $originalData['custom_fields_emails'])
                : (array) $originalData['custom_fields_emails'];

            $emails = array_map('trim', $emails);
            $emails = array_filter($emails, fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL));

            if ($emails !== []) {
                $existingPerson = People::query()
                    ->when($teamId, fn ($q) => $q->where('team_id', $teamId))
                    ->whereHas('customFieldValues', function ($q) use ($emails): void {
                        $q->where('custom_field_id', function ($subQuery): void {
                            $subQuery->select('id')
                                ->from('custom_fields')
                                ->where('code', 'emails');
                        })
                            ->where(function ($emailQuery) use ($emails): void {
                                foreach ($emails as $email) {
                                    $emailQuery->orWhereJsonContains('value_json', $email);
                                }
                            });
                    })
                    ->first();
            }
        }

        return $existingPerson ?? new People;
    }

    public function getValidationMessages(): array
    {
        return [
            'name.required' => 'The person name is required and cannot be empty.',
            'name.max' => 'The person name cannot exceed 255 characters.',
            'company_name.max' => 'The company name cannot exceed 255 characters.',
        ];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your people import has completed and '.number_format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if (($failedRowsCount = $import->getFailedRowsCount()) !== 0) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}

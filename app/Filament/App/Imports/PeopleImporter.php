<?php

declare(strict_types=1);

namespace App\Filament\App\Imports;

use App\Enums\CreationSource;
use App\Models\Company;
use App\Models\People;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Relaticle\CustomFields\Filament\Imports\CustomFieldsImporter;

final class PeopleImporter extends Importer
{
    protected static ?string $model = People::class;

    /**
     * Get the team ID for this import
     */
    private function getTeamId(): ?int
    {
        return $this->import->team_id;
    }

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
        $teamId = $this->getTeamId();

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

            if (! empty($emails)) {
                $existingPerson = People::query()
                    ->when($teamId, function ($q) use ($teamId) {
                        return $q->where('team_id', $teamId);
                    })
                    ->whereHas('customFieldValues', function ($q) use ($emails) {
                        $q->where('custom_field_id', function ($subQuery) {
                            $subQuery->select('id')
                                ->from('custom_fields')
                                ->where('code', 'emails');
                        })
                            ->where(function ($emailQuery) use ($emails) {
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

    // Before filling model with data, remove custom fields
    protected function beforeFill(): void
    {
        try {
            // Filter custom fields using the CustomFieldsImporter
            $this->data = app(CustomFieldsImporter::class)->filterCustomFieldsFromData($this->data);

            // Exclude custom fields and company fields from the data that goes to the model
            $this->data = array_filter($this->data, function ($key) {
                // Exclude custom fields, company_name, and Company from the data
                return ! str_starts_with($key, 'custom_fields_')
                    && $key !== 'company_name'
                    && $key !== 'Company';
            }, ARRAY_FILTER_USE_KEY);

        } catch (\Exception $e) {
            // Fallback: just remove custom fields manually
            $this->data = array_filter($this->data ?? [], function ($key) {
                return ! str_starts_with($key, 'custom_fields_')
                    && $key !== 'company_name'
                    && $key !== 'Company';
            }, ARRAY_FILTER_USE_KEY);
        }
    }

    protected function beforeSave(): void
    {
        // Set team_id from import
        $teamId = $this->getTeamId();
        if ($teamId) {
            $this->record->setAttribute('team_id', $teamId);
        }

        // Set creator if this is a new record and user is authenticated
        if (! $this->record->exists) {
            $this->record->setAttribute('creator_id', $this->import->user_id);
        }

        // Set creation source
        $this->record->setAttribute('creation_source', CreationSource::IMPORT);

        // Handle company assignment
        $originalData = $this->getOriginalData();

        // Check for company name in multiple possible field names
        $companyName = null;
        if (! empty($originalData['company_name'])) {
            $companyName = $originalData['company_name'];
        } elseif (! empty($originalData['Company'])) {
            $companyName = $originalData['Company'];
        }

        if ($companyName) {
            $this->assignCompany($companyName);
        }

        // Remove company fields from data to avoid conflicts
        unset($this->data['company_name']);
        unset($this->data['Company']);
    }

    protected function afterSave(): void
    {
        try {
            // Get the tenant if using multi-tenancy
            $tenant = Filament::getTenant();
            $originalData = $this->getOriginalData();

            app(CustomFieldsImporter::class)->saveCustomFieldValues(
                $this->record,      // The model instance
                $originalData,      // Original data with custom fields
                $tenant            // Optional: tenant for multi-tenancy
            );

        } catch (\Exception $e) {
            report($e);
        }
    }

    /**
     * Assign company to the person, creating it if necessary
     */
    private function assignCompany(string $companyName): void
    {
        $teamId = $this->getTeamId();

        if (! $teamId) {
            report('No team ID available for company assignment.');

            return;
        }

        try {
            $company = Company::query()->firstOrCreate(
                [
                    'name' => trim($companyName),
                    'team_id' => $teamId,
                ],
                [
                    'creator_id' => $this->import->user_id,
                    'creation_source' => CreationSource::IMPORT,
                ]
            );

            $this->record->setAttribute('company_id', $company->getKey());

        } catch (\Exception $e) {
            report($e);
        }
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

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}

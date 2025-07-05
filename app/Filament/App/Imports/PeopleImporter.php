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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Relaticle\CustomFields\Filament\Imports\CustomFieldsImporter;

final class PeopleImporter extends Importer
{
    protected static ?string $model = People::class;

    /**
     * @param  array<string, mixed>  $columnMap
     * @param  array<string, mixed>  $options
     */
    public function __construct(Import $import, array $columnMap, array $options)
    {
        parent::__construct($import, $columnMap, $options);

        // Store team ID on import for consistency
        $import->team_id = Auth::user()->currentTeam?->getKey();
    }

    /**
     * @return array<int, ImportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->guess(['name', 'full_name', 'person_name'])
                ->rules(['required', 'string', 'max:255'])
                ->example('John Doe'),

            ImportColumn::make('company_name')
                ->label('Company Name')
                ->guess(['company_name', 'Company'])
                ->rules(['nullable', 'string', 'max:255'])
                ->example('Acme Corporation'),

            ...app(CustomFieldsImporter::class)->getColumns(self::getModel()),
        ];
    }

    public function resolveRecord(): People
    {
        // Try to find by exact name match first
        $person = $this->findByName($this->data['name']);

        // If not found, try to find by email
        if (! $person) {
            $person = $this->findByEmail();
        }

        return $person ?? new People;
    }

    protected function beforeFill(): void
    {
        try {
            // Use the custom fields importer to filter out custom fields
            $this->data = app(CustomFieldsImporter::class)->filterCustomFieldsFromData($this->data);
        } catch (\Exception $e) {
            // Fallback: manually filter custom fields
            $this->data = array_filter($this->data ?? [], function ($key) {
                return ! str_starts_with($key, 'custom_fields_');
            }, ARRAY_FILTER_USE_KEY);
        }

        // Remove company-related fields that aren't part of the People model
        unset($this->data['company_name']);
        unset($this->data['Company']);
    }

    protected function beforeSave(): void
    {
        $this->record->fill([
            'team_id' => $this->import->team_id,
            'creation_source' => CreationSource::IMPORT,
        ]);

        // Set creator only for new records
        if (! $this->record->exists) {
            $this->record->creator_id = $this->import->user_id;
        }

        // Handle company assignment
        $this->handleCompanyAssignment();
    }

    protected function afterSave(): void
    {
        try {
            app(CustomFieldsImporter::class)->saveCustomFieldValues(
                $this->record,
                $this->getOriginalData(),
                Filament::getTenant()
            );
        } catch (\Exception $e) {
            report($e);
        }
    }

    private function findByName(string $name): ?People
    {
        return People::query()
            ->where('name', $name)
            ->when($this->import->team_id, fn (Builder $query) => $query->where('team_id', $this->import->team_id))
            ->first();
    }

    private function findByEmail(): ?People
    {
        $emails = $this->extractEmails();

        if (empty($emails)) {
            return null;
        }

        return People::query()
            ->when($this->import->team_id, fn (Builder $query) => $query->where('team_id', $this->import->team_id))
            ->whereHas('customFieldValues', function (Builder $query) use ($emails) {
                $query->whereRelation('customField', 'code', 'emails')
                    ->where(function (Builder $query) use ($emails) {
                        foreach ($emails as $email) {
                            $query->orWhereJsonContains('value_json', $email);
                        }
                    });
            })
            ->first();
    }

    /**
     * Extract and validate emails from original data
     *
     * @return array<int, string>
     */
    private function extractEmails(): array
    {
        $emailsField = $this->getOriginalData()['custom_fields_emails'] ?? null;

        if (empty($emailsField)) {
            return [];
        }

        $emails = is_string($emailsField)
            ? explode(',', $emailsField)
            : (array) $emailsField;

        return collect($emails)
            ->map(fn ($email) => trim((string) $email))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->values()
            ->toArray();
    }

    private function handleCompanyAssignment(): void
    {
        $companyName = $this->getCompanyName();

        if (! $companyName || ! $this->import->team_id) {
            return;
        }

        try {
            $company = Company::firstOrCreate(
                [
                    'name' => $companyName,
                    'team_id' => $this->import->team_id,
                ],
                [
                    'creator_id' => $this->import->user_id,
                    'creation_source' => CreationSource::IMPORT,
                ]
            );

            $this->record->company_id = $company->getKey();
        } catch (\Exception $e) {
            report($e);
        }
    }

    private function getCompanyName(): ?string
    {
        $originalData = $this->getOriginalData();

        $companyName = $originalData['company_name'] ?? $originalData['Company'] ?? null;

        return $companyName ? trim((string) $companyName) : null;
    }

    /**
     * @return array<string, string>
     */
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
        $successCount = number_format($import->successful_rows);
        $body = "Your people import has completed and {$successCount} ".str('row')->plural($import->successful_rows).' imported.';

        if ($failedCount = $import->getFailedRowsCount()) {
            $failedFormatted = number_format($failedCount);
            $body .= " {$failedFormatted} ".str('row')->plural($failedCount).' failed to import.';
        }

        return $body;
    }
}

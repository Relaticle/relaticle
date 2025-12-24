<?php

declare(strict_types=1);

namespace Relaticle\ImportWizard\Livewire\Concerns;

use League\Csv\SyntaxError;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Relaticle\ImportWizard\Services\CsvService;

/**
 * Provides CSV/Excel file parsing functionality for the Import Wizard.
 *
 * Simplified to use CsvService for all file operations.
 */
trait HasCsvParsing
{
    /**
     * Parse the uploaded file and extract headers and row count.
     */
    protected function parseUploadedFile(): void
    {
        if ($this->uploadedFile === null) {
            return;
        }

        $csvService = app(CsvService::class);

        // Convert Excel to CSV if needed and persist
        $csvPath = $csvService->processUploadedFile($this->uploadedFile);
        if ($csvPath === null) {
            $this->addError('uploadedFile', 'Failed to process file');

            return;
        }

        $this->persistedFilePath = $csvPath;

        try {
            // Parse the CSV
            $csvReader = $csvService->createReader($csvPath);
            $this->csvHeaders = $csvReader->getHeader();
            $this->rowCount = $csvService->countRows($csvPath);

            // Validate row count (max 10,000 rows for performance)
            if ($this->rowCount > 10000) {
                $this->addError(
                    'uploadedFile',
                    'This file contains '.number_format($this->rowCount).' rows. '.
                    'The maximum is 10,000 rows per import. '.
                    'Please split your file into smaller chunks, or contact support for assistance with bulk imports.'
                );
                $this->cleanupTempFile();
                $this->persistedFilePath = null;
                $this->rowCount = 0;

                return;
            }
        } catch (SyntaxError $e) {
            // Handle duplicate column names
            $duplicates = $e->duplicateColumnNames();
            if ($duplicates !== []) {
                $this->addError('uploadedFile', 'Your CSV has duplicate column names: '.implode(', ', $duplicates).'. Please rename them to be unique.');
            } else {
                $this->addError('uploadedFile', 'CSV syntax error: '.$e->getMessage());
            }
            $this->cleanupTempFile();
            $this->persistedFilePath = null;
            $this->rowCount = 0;
        }
    }

    /**
     * Clean up temporary files.
     */
    protected function cleanupTempFile(): void
    {
        app(CsvService::class)->cleanup($this->persistedFilePath);
    }

    /**
     * Get preview values for a specific CSV column.
     *
     * @return array<int, string>
     */
    public function getColumnPreviewValues(string $csvColumn, int $limit = 5): array
    {
        if ($this->persistedFilePath === null) {
            return [];
        }

        $csvReader = app(CsvService::class)->createReader($this->persistedFilePath);

        return collect($csvReader->getRecords())
            ->take($limit)
            ->pluck($csvColumn)
            ->map(fn (mixed $value): string => (string) $value)
            ->values()
            ->toArray();
    }
}

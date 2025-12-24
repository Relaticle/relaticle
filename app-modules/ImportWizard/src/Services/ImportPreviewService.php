<?php

declare(strict_types=1);

namespace Relaticle\ImportWizard\Services;

use Illuminate\Support\Str;
use League\Csv\Statement;
use Relaticle\ImportWizard\Data\ImportPreviewResult;
use Relaticle\ImportWizard\Filament\Imports\OpportunityImporter;
use Relaticle\ImportWizard\Filament\Imports\PeopleImporter;

/**
 * Service for previewing import results without actually saving data.
 *
 * Uses direct database lookups via ImportRecordResolver for O(1) performance.
 * No reflection - simpler, faster, upgrade-proof.
 */
final readonly class ImportPreviewService
{
    public function __construct(
        private CsvService $csvService,
        private CompanyMatcher $companyMatcher,
        private ImportRecordResolver $recordResolver,
    ) {}

    /**
     * Generate a preview of what an import will do.
     *
     * @param  class-string  $importerClass
     * @param  array<string, string>  $columnMap  Maps importer field name to CSV column name
     * @param  array<string, mixed>  $options  Import options
     * @param  array<string, array<string, string>>  $valueCorrections  User-defined value corrections
     * @param  int  $sampleSize  Maximum number of rows to process for preview (default 1,000)
     */
    public function preview(
        string $importerClass,
        string $csvPath,
        array $columnMap,
        array $options,
        string $teamId,
        string $userId,
        array $valueCorrections = [],
        int $sampleSize = 1000,
    ): ImportPreviewResult {
        $csvReader = $this->csvService->createReader($csvPath);
        $totalRows = $this->csvService->countRows($csvPath);

        // Process only sampled rows for preview
        $records = (new Statement)->limit($sampleSize)->process($csvReader);

        // Pre-load all records for fast O(1) lookups
        $this->recordResolver->loadForTeam($teamId, $importerClass);

        $willCreate = 0;
        $willUpdate = 0;
        $rows = [];

        $rowNumber = 0;
        foreach ($records as $record) {
            $rowNumber++;

            // Apply value corrections
            $record = $this->applyCorrections($record, $columnMap, $valueCorrections);

            // Format row with mapped field names
            $formattedRow = $this->formatRowRecord($record, $columnMap);

            // Determine action using direct lookup (no reflection!)
            $result = $this->determineAction($formattedRow, $teamId, $importerClass);

            if ($result['action'] === 'create') {
                $willCreate++;
            } else {
                $willUpdate++;
            }

            // Enrich with company match data for People/Opportunity imports
            if ($this->shouldEnrichWithCompanyMatch($importerClass)) {
                $formattedRow = $this->enrichRowWithCompanyMatch($formattedRow, $teamId);
            }

            $rows[] = array_merge(
                $formattedRow,
                [
                    '_row_index' => $rowNumber,
                    '_is_new' => $result['action'] === 'create',
                    '_update_method' => $result['method'],
                    '_record_id' => $result['recordId'],
                ]
            );
        }

        $actualSampleSize = min($totalRows, $sampleSize);
        $isSampled = $totalRows > $sampleSize;

        // Scale counts to full dataset if sampled
        $scaledCreateCount = $willCreate;
        $scaledUpdateCount = $willUpdate;

        if ($isSampled && $actualSampleSize > 0) {
            $scaleFactor = $totalRows / $actualSampleSize;
            $scaledCreateCount = (int) round($willCreate * $scaleFactor);
            $scaledUpdateCount = (int) round($willUpdate * $scaleFactor);
        }

        return new ImportPreviewResult(
            totalRows: $totalRows,
            createCount: $scaledCreateCount,
            updateCount: $scaledUpdateCount,
            rows: $rows,
            isSampled: $isSampled,
            sampleSize: $actualSampleSize,
        );
    }

    /**
     * Determine what action would be taken for a row using direct lookup.
     *
     * No reflection - uses ImportRecordResolver's pre-loaded cache for O(1) lookups.
     *
     * @param  array<string, mixed>  $row
     * @param  class-string  $importerClass
     * @return array{action: string, method: string|null, recordId: string|null}
     */
    private function determineAction(array $row, string $teamId, string $importerClass): array
    {
        // 1. Check for ID-based match (highest priority)
        $id = $row['id'] ?? null;
        if (! blank($id) && Str::isUlid((string) $id)) {
            $record = $this->recordResolver->resolveById((string) $id, $teamId, $importerClass);
            if ($record !== null) {
                return [
                    'action' => 'update',
                    'method' => 'id',
                    'recordId' => $record->getKey(),
                ];
            }
            // Invalid ID - will create new record
            return ['action' => 'create', 'method' => null, 'recordId' => null];
        }

        // 2. Check for attribute-based match (name, email, etc.)
        $record = $this->resolveByAttributes($row, $teamId, $importerClass);
        if ($record !== null) {
            return [
                'action' => 'update',
                'method' => 'attribute',
                'recordId' => $record->getKey(),
            ];
        }

        return ['action' => 'create', 'method' => null, 'recordId' => null];
    }

    /**
     * Resolve a record by its unique attributes (name, email, etc.)
     *
     * @param  array<string, mixed>  $row
     * @param  class-string  $importerClass
     */
    private function resolveByAttributes(array $row, string $teamId, string $importerClass): ?object
    {
        // Company: match by name
        if (str_contains($importerClass, 'CompanyImporter')) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name !== '') {
                return $this->recordResolver->resolveCompanyByName($name, $teamId);
            }
        }

        // People: match by email
        if (str_contains($importerClass, 'PeopleImporter')) {
            $emails = $this->extractEmailsFromRow($row);
            if ($emails !== []) {
                return $this->recordResolver->resolvePersonByEmail($emails, $teamId);
            }
        }

        // Opportunity: match by name
        if (str_contains($importerClass, 'OpportunityImporter')) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name !== '') {
                return $this->recordResolver->resolveOpportunityByName($name, $teamId);
            }
        }

        // Task: match by title
        if (str_contains($importerClass, 'TaskImporter')) {
            $title = trim((string) ($row['title'] ?? ''));
            if ($title !== '') {
                return $this->recordResolver->resolveTaskByTitle($title, $teamId);
            }
        }

        // Note: match by title
        if (str_contains($importerClass, 'NoteImporter')) {
            $title = trim((string) ($row['title'] ?? ''));
            if ($title !== '') {
                return $this->recordResolver->resolveNoteByTitle($title, $teamId);
            }
        }

        return null;
    }

    /**
     * Apply value corrections to a row.
     *
     * @param  array<string, mixed>  $record
     * @param  array<string, string>  $columnMap
     * @param  array<string, array<string, string>>  $corrections
     * @return array<string, mixed>
     */
    private function applyCorrections(array $record, array $columnMap, array $corrections): array
    {
        foreach ($corrections as $fieldName => $valueMappings) {
            $csvColumn = $columnMap[$fieldName] ?? null;
            if ($csvColumn === null || ! isset($record[$csvColumn])) {
                continue;
            }

            $currentValue = $record[$csvColumn];
            if (isset($valueMappings[$currentValue])) {
                $record[$csvColumn] = $valueMappings[$currentValue];
            }
        }

        return $record;
    }

    /**
     * Format a row record with mapped field names.
     *
     * @param  array<string, mixed>  $record
     * @param  array<string, string>  $columnMap
     * @return array<string, mixed>
     */
    private function formatRowRecord(array $record, array $columnMap): array
    {
        $formatted = [];

        foreach ($columnMap as $fieldName => $csvColumn) {
            if ($csvColumn !== '' && isset($record[$csvColumn])) {
                $formatted[$fieldName] = $record[$csvColumn];
            }
        }

        return $formatted;
    }

    /**
     * Check if the importer should have company match enrichment.
     *
     * @param  class-string  $importerClass
     */
    private function shouldEnrichWithCompanyMatch(string $importerClass): bool
    {
        return in_array($importerClass, [
            PeopleImporter::class,
            OpportunityImporter::class,
        ], true);
    }

    /**
     * Enrich a row with company match data for transparent preview.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function enrichRowWithCompanyMatch(array $row, string $teamId): array
    {
        $companyName = (string) ($row['company_name'] ?? '');
        $emails = $this->extractEmailsFromRow($row);

        $matchResult = $this->companyMatcher->match($companyName, $emails, $teamId);

        return array_merge($row, [
            '_company_name' => $matchResult->companyName,
            '_company_match_type' => $matchResult->matchType,
            '_company_match_count' => $matchResult->matchCount,
            '_company_id' => $matchResult->companyId,
        ]);
    }

    /**
     * Extract emails from a row for company matching.
     *
     * @param  array<string, mixed>  $row
     * @return array<string>
     */
    private function extractEmailsFromRow(array $row): array
    {
        $emailsRaw = $row['custom_fields_emails'] ?? null;

        if ($emailsRaw === null || $emailsRaw === '') {
            return [];
        }

        $emails = is_string($emailsRaw)
            ? array_map(trim(...), explode(',', $emailsRaw))
            : (array) $emailsRaw;

        return array_values(array_filter(
            $emails,
            static fn (mixed $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false
        ));
    }
}

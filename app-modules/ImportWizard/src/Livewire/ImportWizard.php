<?php

declare(strict_types=1);

namespace Relaticle\ImportWizard\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Imports\ImportColumn;
use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\View as ViewComponent;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\View\View;
use League\Csv\SyntaxError;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Relaticle\ImportWizard\Data\ColumnAnalysis;
use Relaticle\ImportWizard\Data\ImportPreviewResult;
use Relaticle\ImportWizard\Enums\DuplicateHandlingStrategy;
use Relaticle\ImportWizard\Filament\Concerns\HasImportEntities;
use Relaticle\ImportWizard\Filament\Imports\BaseImporter;
use Relaticle\ImportWizard\Jobs\StreamingImportCsv;
use Relaticle\ImportWizard\Models\Import;
use Relaticle\ImportWizard\Services\CsvAnalyzer;
use Relaticle\ImportWizard\Services\CsvService;
use Relaticle\ImportWizard\Services\ImportPreviewService;

/**
 * 4-step import wizard using Filament's native Wizard component.
 */
final class ImportWizard extends Component implements HasActions, HasForms
{
    use HasImportEntities;
    use InteractsWithActions;
    use InteractsWithForms;
    use WithFileUploads;

    private const int PREVIEW_SAMPLE_SIZE = 50;

    #[Locked]
    public string $entityType = 'companies';

    #[Locked]
    public ?string $returnUrl = null;

    // Step 1: Upload
    #[Validate('required|file|max:51200')]
    public mixed $uploadedFile = null;
    public ?string $persistedFilePath = null;
    public int $rowCount = 0;
    /** @var array<string> */
    public array $csvHeaders = [];

    // Step 2: Column mapping
    /** @var array<string, string> */
    public array $columnMap = [];

    // Step 3: Value analysis
    /** @var array<array<string, mixed>> */
    public array $columnAnalysesData = [];
    /** @var array<string, array<string, string>> */
    public array $valueCorrections = [];
    public int $reviewPage = 1;
    public ?string $expandedColumn = null;

    // Step 4: Preview
    /** @var array<string, mixed>|null */
    public ?array $previewResultData = null;
    /** @var array<int, array<string, mixed>> */
    public array $previewRows = [];

    // ═══════════════════════════════════════════════════════════════════════
    // FILAMENT WIZARD FORM
    // ═══════════════════════════════════════════════════════════════════════

    public function form(Form $form): Form
    {
        return $form->schema([
            Wizard::make([
                Step::make('Upload')
                    ->icon(Heroicon::ArrowUpTray)
                    ->description('Select your CSV or Excel file')
                    ->schema([
                        ViewComponent::make('import-wizard::steps.upload'),
                    ])
                    ->afterValidation(function (): void {
                        if ($this->persistedFilePath === null || $this->csvHeaders === []) {
                            Notification::make()->title('Please upload a valid file')->warning()->send();
                            throw new Halt;
                        }
                        $this->autoMapColumns();
                    }),

                Step::make('Map Columns')
                    ->icon(Heroicon::ArrowsRightLeft)
                    ->description('Match file columns to attributes')
                    ->schema([
                        ViewComponent::make('import-wizard::steps.map'),
                    ])
                    ->afterValidation(function (): void {
                        if (! $this->hasAllRequiredMappings()) {
                            Notification::make()->title('Please map all required fields')->warning()->send();
                            throw new Halt;
                        }
                        if (! $this->hasUniqueIdentifierMapped()) {
                            $this->mountAction('proceedWithoutUniqueIdentifiers');
                            throw new Halt;
                        }
                        $this->analyzeColumns();
                    }),

                Step::make('Review')
                    ->icon(Heroicon::MagnifyingGlass)
                    ->description('Fix validation issues')
                    ->schema([
                        ViewComponent::make('import-wizard::steps.review'),
                    ])
                    ->afterValidation(function (): void {
                        if ($this->hasValidationErrors()) {
                            $this->mountAction('proceedWithErrors');
                            throw new Halt;
                        }
                        $this->generateImportPreview();
                    }),

                Step::make('Preview')
                    ->icon(Heroicon::Eye)
                    ->description('Review and start import')
                    ->schema([
                        ViewComponent::make('import-wizard::steps.preview'),
                    ]),
            ])
                ->submitAction(new HtmlString(<<<'HTML'
                    <x-filament::button type="button" wire:click="executeImport" wire:loading.attr="disabled">
                        Start Import
                    </x-filament::button>
                HTML)),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // COMPUTED PROPERTIES
    // ═══════════════════════════════════════════════════════════════════════

    /** @return array<ImportColumn> */
    #[Computed]
    public function importerColumns(): array
    {
        $importerClass = $this->getImporterClass();

        return $importerClass ? $importerClass::getColumns() : [];
    }

    /** @return Collection<int, ColumnAnalysis> */
    #[Computed]
    public function columnAnalyses(): Collection
    {
        return collect($this->columnAnalysesData)
            ->map(fn (array $data): ColumnAnalysis => ColumnAnalysis::from($data));
    }

    #[Computed]
    public function previewResult(): ?ImportPreviewResult
    {
        return $this->previewResultData ? ImportPreviewResult::from($this->previewResultData) : null;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // STEP 1: FILE UPLOAD
    // ═══════════════════════════════════════════════════════════════════════

    public function updatedUploadedFile(): void
    {
        $this->resetErrorBag('uploadedFile');
        $this->parseUploadedFile();
    }

    protected function parseUploadedFile(): void
    {
        if ($this->uploadedFile === null) {
            return;
        }

        $csvService = app(CsvService::class);
        $csvPath = $csvService->processUploadedFile($this->uploadedFile);

        if ($csvPath === null) {
            $this->addError('uploadedFile', 'Failed to process file');

            return;
        }

        $this->persistedFilePath = $csvPath;

        try {
            $csvReader = $csvService->createReader($csvPath);
            $this->csvHeaders = $csvReader->getHeader();
            $this->rowCount = $csvService->countRows($csvPath);

            if ($this->rowCount > 10000) {
                $this->addError('uploadedFile', 'Maximum 10,000 rows per import. This file has '.number_format($this->rowCount).'.');
                $this->cleanupTempFile();
                $this->persistedFilePath = null;
                $this->rowCount = 0;
            }
        } catch (SyntaxError $e) {
            $duplicates = $e->duplicateColumnNames();
            $this->addError('uploadedFile', $duplicates !== []
                ? 'Duplicate column names: '.implode(', ', $duplicates)
                : 'CSV syntax error: '.$e->getMessage());
            $this->cleanupTempFile();
            $this->persistedFilePath = null;
            $this->rowCount = 0;
        }
    }

    public function removeFile(): void
    {
        $this->cleanupTempFile();
        $this->uploadedFile = null;
        $this->persistedFilePath = null;
        $this->rowCount = 0;
        $this->csvHeaders = [];
        $this->columnMap = [];
    }

    protected function cleanupTempFile(): void
    {
        app(CsvService::class)->cleanup($this->persistedFilePath);
    }

    /** @return array<int, string> */
    public function getColumnPreviewValues(string $csvColumn, int $limit = 5): array
    {
        if ($this->persistedFilePath === null) {
            return [];
        }

        return collect(app(CsvService::class)->createReader($this->persistedFilePath)->getRecords())
            ->take($limit)
            ->pluck($csvColumn)
            ->map(fn (mixed $v): string => (string) $v)
            ->values()
            ->toArray();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // STEP 2: COLUMN MAPPING
    // ═══════════════════════════════════════════════════════════════════════

    protected function autoMapColumns(): void
    {
        if ($this->csvHeaders === [] || $this->importerColumns === []) {
            return;
        }

        $csvHeadersLower = collect($this->csvHeaders)
            ->mapWithKeys(fn (string $h): array => [Str::lower($h) => $h]);

        $this->columnMap = collect($this->importerColumns)
            ->mapWithKeys(function (ImportColumn $col) use ($csvHeadersLower): array {
                $guesses = collect($col->getGuesses())->map(fn (string $g): string => Str::lower($g));
                $match = $guesses->first(fn (string $g): bool => $csvHeadersLower->has($g));

                return [$col->getName() => $match ? $csvHeadersLower->get($match) : ''];
            })
            ->toArray();
    }

    /** @return class-string<BaseImporter>|null */
    protected function getImporterClass(): ?string
    {
        return $this->getEntities()[$this->entityType]['importer'] ?? null;
    }

    /** @return class-string|null */
    protected function getModelClass(): ?string
    {
        $importerClass = $this->getImporterClass();

        return $importerClass !== null ? $importerClass::getModel() : null;
    }

    public function hasAllRequiredMappings(): bool
    {
        return collect($this->importerColumns)
            ->filter(fn (ImportColumn $c): bool => $c->isMappingRequired())
            ->every(fn (ImportColumn $c): bool => ($this->columnMap[$c->getName()] ?? '') !== '');
    }

    public function mapCsvColumnToField(string $csvColumn, string $fieldName): void
    {
        foreach ($this->columnMap as $field => $csv) {
            if ($csv === $csvColumn) {
                $this->columnMap[$field] = '';
            }
        }
        if ($fieldName !== '') {
            $this->columnMap[$fieldName] = $csvColumn;
        }
    }

    protected function hasUniqueIdentifierMapped(): bool
    {
        $importerClass = $this->getImporterClass();
        if ($importerClass === null || $importerClass::skipUniqueIdentifierWarning()) {
            return true;
        }

        foreach ($importerClass::getUniqueIdentifierColumns() as $column) {
            if (($this->columnMap[$column] ?? '') !== '') {
                return true;
            }
        }

        return false;
    }

    protected function getMissingUniqueIdentifiersMessage(): string
    {
        $importerClass = $this->getImporterClass();

        return $importerClass !== null
            ? $importerClass::getMissingUniqueIdentifiersMessage()
            : 'Map a Record ID column';
    }

    // ═══════════════════════════════════════════════════════════════════════
    // STEP 3: VALUE ANALYSIS
    // ═══════════════════════════════════════════════════════════════════════

    protected function analyzeColumns(): void
    {
        if ($this->persistedFilePath === null) {
            $this->columnAnalysesData = [];

            return;
        }

        $analyses = app(CsvAnalyzer::class)->analyze(
            csvPath: $this->persistedFilePath,
            columnMap: $this->columnMap,
            importerColumns: $this->importerColumns,
            entityType: $this->getModelClass(),
        );

        $this->columnAnalysesData = $analyses->map(fn (ColumnAnalysis $a): array => $a->toArray())->toArray();
        $this->reviewPage = 1;
        $this->expandedColumn = $this->columnAnalyses->first()?->mappedToField;
    }

    public function hasValidationErrors(): bool
    {
        return $this->columnAnalyses->contains(fn (ColumnAnalysis $a): bool => $a->hasErrors());
    }

    public function getTotalErrorCount(): int
    {
        return $this->columnAnalyses->sum(fn (ColumnAnalysis $a): int => $a->getErrorCount());
    }

    public function correctValue(string $fieldName, string $oldValue, string $newValue): void
    {
        $this->valueCorrections[$fieldName] ??= [];
        $this->valueCorrections[$fieldName][$oldValue] = $newValue;
        $this->revalidateCorrectedValue($fieldName, $oldValue, $newValue);
    }

    private function revalidateCorrectedValue(string $fieldName, string $oldValue, string $newValue): void
    {
        $idx = collect($this->columnAnalysesData)->search(fn (array $d): bool => $d['mappedToField'] === $fieldName);
        if ($idx === false) {
            return;
        }

        $issues = collect($this->columnAnalysesData[$idx]['issues'])
            ->reject(fn (array $i): bool => $i['value'] === $oldValue)
            ->values()
            ->toArray();

        if ($newValue !== '') {
            $error = app(CsvAnalyzer::class)->validateSingleValue(
                value: $newValue,
                fieldName: $fieldName,
                importerColumns: $this->importerColumns,
                entityType: $this->getModelClass(),
            );

            if ($error !== null) {
                $issues[] = [
                    'value' => $oldValue,
                    'message' => $error,
                    'rowCount' => $this->columnAnalysesData[$idx]['uniqueValues'][$oldValue] ?? 1,
                    'severity' => 'error',
                ];
            }
        }

        $this->columnAnalysesData[$idx]['issues'] = $issues;
    }

    public function skipValue(string $fieldName, string $oldValue): void
    {
        if ($this->isValueSkipped($fieldName, $oldValue)) {
            unset($this->valueCorrections[$fieldName][$oldValue]);
            if (empty($this->valueCorrections[$fieldName])) {
                unset($this->valueCorrections[$fieldName]);
            }

            return;
        }
        $this->correctValue($fieldName, $oldValue, '');
    }

    public function isValueSkipped(string $fieldName, string $value): bool
    {
        return isset($this->valueCorrections[$fieldName][$value])
            && $this->valueCorrections[$fieldName][$value] === '';
    }

    public function getCorrectedValue(string $fieldName, string $originalValue): ?string
    {
        return $this->valueCorrections[$fieldName][$originalValue] ?? null;
    }

    public function hasCorrectionForValue(string $fieldName, string $value): bool
    {
        return isset($this->valueCorrections[$fieldName][$value]);
    }

    public function toggleColumn(string $columnName): void
    {
        $this->expandedColumn = $this->expandedColumn === $columnName ? null : $columnName;
        $this->reviewPage = 1;
    }

    public function loadMoreValues(): void
    {
        $this->reviewPage++;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // STEP 4: PREVIEW
    // ═══════════════════════════════════════════════════════════════════════

    protected function generateImportPreview(): void
    {
        $importerClass = $this->getImporterClass();
        $team = Filament::getTenant();
        $user = auth()->user();

        if ($importerClass === null || $this->persistedFilePath === null || $team === null || $user === null) {
            $this->previewResultData = null;
            $this->previewRows = [];

            return;
        }

        $result = app(ImportPreviewService::class)->preview(
            importerClass: $importerClass,
            csvPath: $this->persistedFilePath,
            columnMap: $this->columnMap,
            options: ['duplicate_handling' => DuplicateHandlingStrategy::SKIP],
            teamId: $team->getKey(),
            userId: $user->getAuthIdentifier(),
            valueCorrections: $this->valueCorrections,
            sampleSize: min($this->rowCount, 1000),
        );

        $this->previewResultData = [
            'totalRows' => $result->totalRows,
            'createCount' => $result->createCount,
            'updateCount' => $result->updateCount,
            'rows' => [],
            'isSampled' => $result->isSampled,
            'sampleSize' => $result->sampleSize,
        ];
        $this->previewRows = array_slice($result->rows, 0, self::PREVIEW_SAMPLE_SIZE);
    }

    public function hasRecordsToImport(): bool
    {
        return ($this->previewResultData['totalRows'] ?? 0) > 0;
    }

    public function getCreateCount(): int
    {
        return $this->previewResultData['createCount'] ?? 0;
    }

    public function getUpdateCount(): int
    {
        return $this->previewResultData['updateCount'] ?? 0;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // IMPORT EXECUTION
    // ═══════════════════════════════════════════════════════════════════════

    public function executeImport(): void
    {
        if (! $this->hasRecordsToImport()) {
            Notification::make()->title('No Records to Import')->warning()->send();

            return;
        }

        $team = Filament::getTenant();
        $user = auth()->user();
        $importerClass = $this->getImporterClass();

        if ($team === null || $user === null || $importerClass === null || $this->persistedFilePath === null) {
            Notification::make()->title('Import Error')->danger()->send();

            return;
        }

        $import = Import::create([
            'team_id' => $team->getKey(),
            'user_id' => $user->getAuthIdentifier(),
            'file_name' => $this->uploadedFile?->getClientOriginalName() ?? 'import.csv',
            'file_path' => $this->moveFileToPermanentStorage(),
            'importer' => $importerClass,
            'total_rows' => $this->rowCount,
            'processed_rows' => 0,
            'successful_rows' => 0,
        ]);

        $this->dispatchImportJobs($import);

        Notification::make()
            ->title('Import Started')
            ->body("Importing {$this->rowCount} records.")
            ->success()
            ->send();

        $this->cleanupTempFile();

        if ($this->returnUrl !== null) {
            $this->redirect($this->returnUrl);
        }
    }

    private function calculateOptimalChunkSize(): int
    {
        $columnCount = count($this->columnMap);
        $chunkSize = match (true) {
            $columnCount <= 5 => 500,
            $columnCount <= 10 => 250,
            $columnCount <= 20 => 150,
            default => 75,
        };

        $customFieldCount = collect($this->columnMap)->keys()
            ->filter(fn (string $f): bool => str_starts_with($f, 'custom_fields_'))->count();

        if ($customFieldCount > 0) {
            $chunkSize = (int) ($chunkSize * (1 - min($customFieldCount, 15) / 5 * 0.20));
        }

        $entityPenalty = match ($this->entityType) {
            'opportunities' => 0.8, 'people' => 0.9, default => 1.0,
        };

        return max(50, min(1000, (int) ($chunkSize * $entityPenalty)));
    }

    private function moveFileToPermanentStorage(): string
    {
        $permanentPath = 'imports/'.Str::uuid()->toString().'.csv';

        if ($this->valueCorrections !== []) {
            Storage::disk('local')->put($permanentPath, $this->applyCorrectionsToCsv());
        } else {
            $tempPath = str_replace(Storage::disk('local')->path(''), '', $this->persistedFilePath);
            Storage::disk('local')->copy($tempPath, $permanentPath);
        }

        return $permanentPath;
    }

    private function applyCorrectionsToCsv(): string
    {
        $csvReader = app(CsvService::class)->createReader($this->persistedFilePath);
        $headers = $csvReader->getHeader();

        $output = fopen('php://temp', 'r+');
        if ($output === false) {
            return '';
        }

        fputcsv($output, $headers, escape: '\\');

        foreach ($csvReader->getRecords() as $record) {
            foreach ($this->valueCorrections as $fieldName => $valueMappings) {
                $csvColumn = $this->columnMap[$fieldName] ?? null;
                if ($csvColumn !== null && isset($record[$csvColumn], $valueMappings[$record[$csvColumn]])) {
                    $record[$csvColumn] = $valueMappings[$record[$csvColumn]];
                }
            }
            fputcsv($output, array_values($record), escape: '\\');
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content !== false ? $content : '';
    }

    private function dispatchImportJobs(Import $import): void
    {
        $chunkSize = $this->calculateOptimalChunkSize();
        $jobs = [];
        $currentOffset = 0;

        while ($currentOffset < $this->rowCount) {
            $rowsInChunk = min($chunkSize, $this->rowCount - $currentOffset);
            $jobs[] = new StreamingImportCsv(
                import: $import,
                startRow: $currentOffset,
                rowCount: $rowsInChunk,
                columnMap: $this->columnMap,
                options: ['duplicate_handling' => DuplicateHandlingStrategy::SKIP],
            );
            $currentOffset += $rowsInChunk;
        }

        Bus::batch($jobs)
            ->name("import-{$import->getKey()}")
            ->onQueue('imports')
            ->finally(fn () => $import->update(['completed_at' => now()]))
            ->dispatch();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ACTIONS
    // ═══════════════════════════════════════════════════════════════════════

    public function proceedWithErrorsAction(): Action
    {
        return Action::make('proceedWithErrors')
            ->label('Continue with errors')
            ->icon(Heroicon::OutlinedExclamationTriangle)
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Continue with validation errors?')
            ->modalDescription(fn (): string => $this->getAffectedRowCount().' rows have errors and will be skipped.')
            ->modalSubmitActionLabel('Skip errors and continue')
            ->action(function (): void {
                foreach ($this->columnAnalyses as $analysis) {
                    foreach ($analysis->issues as $issue) {
                        if ($issue->severity === 'error') {
                            $this->skipValue($analysis->mappedToField, $issue->value);
                        }
                    }
                }
                $this->generateImportPreview();
            });
    }

    public function proceedWithoutUniqueIdentifiersAction(): Action
    {
        $docsUrl = route('documentation.show', 'import').'#unique-identifiers';

        return Action::make('proceedWithoutUniqueIdentifiers')
            ->label('Continue without mapping')
            ->icon(Heroicon::OutlinedExclamationTriangle)
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Avoid creating duplicate records')
            ->modalDescription(fn (): HtmlString => new HtmlString(
                'To avoid duplicates, map: <strong>'.$this->getMissingUniqueIdentifiersMessage().'</strong><br><br>'.
                '<a href="'.$docsUrl.'" target="_blank" class="text-primary-600 hover:underline">Learn more</a>'
            ))
            ->modalSubmitActionLabel('Continue without mapping')
            ->modalCancelActionLabel('Go back')
            ->action(fn () => $this->analyzeColumns());
    }

    private function getAffectedRowCount(): int
    {
        $count = 0;
        foreach ($this->columnAnalyses as $analysis) {
            foreach ($analysis->issues as $issue) {
                if ($issue->severity === 'error') {
                    $count += $issue->rowCount;
                }
            }
        }

        return $count;
    }

    public function render(): View
    {
        return view('import-wizard::livewire.import-wizard');
    }
}

<?php

declare(strict_types=1);

namespace Relaticle\ImportWizard\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Csv\Reader as CsvReader;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Unified CSV service consolidating reader creation, row counting, and file handling.
 *
 * Replaces: CsvReaderFactory (merged), fastRowCount duplication (eliminated)
 */
final class CsvService
{
    public function __construct(
        private readonly ExcelToCsvConverter $excelConverter,
    ) {}

    /**
     * Create a CSV reader with auto-detected delimiter.
     *
     * @return CsvReader<array<string, mixed>>
     */
    public function createReader(string $csvPath, int $headerOffset = 0): CsvReader
    {
        $csvReader = CsvReader::createFromPath($csvPath);
        $csvReader->setHeaderOffset($headerOffset);

        $delimiter = $this->detectDelimiter($csvPath);
        if ($delimiter !== null) {
            $csvReader->setDelimiter($delimiter);
        }

        return $csvReader;
    }

    /**
     * Fast row counting with file size estimation for large files.
     *
     * For small files (< 1MB), uses exact counting.
     * For large files, samples rows and estimates based on average row size.
     */
    public function countRows(string $csvPath): int
    {
        $fileSize = filesize($csvPath);
        if ($fileSize === false) {
            return $this->exactRowCount($csvPath);
        }

        // For small files (< 1MB), exact count is fast
        if ($fileSize < 1_048_576) {
            return $this->exactRowCount($csvPath);
        }

        return $this->estimateRowCount($csvPath, $fileSize);
    }

    /**
     * Process uploaded file: convert Excel if needed and persist to storage.
     *
     * @return string|null Path to persisted CSV file, or null on failure
     */
    public function processUploadedFile(TemporaryUploadedFile $file): ?string
    {
        $uploadedFile = new UploadedFile(
            $file->getRealPath(),
            $file->getClientOriginalName(),
            $file->getMimeType(),
        );

        // Convert Excel to CSV if needed
        if ($this->excelConverter->isExcelFile($uploadedFile)) {
            $uploadedFile = $this->excelConverter->convert($uploadedFile);
        }

        // Persist to storage
        $storagePath = 'temp-imports/'.Str::uuid()->toString().'.csv';
        $content = file_get_contents($uploadedFile->getRealPath());

        if ($content === false) {
            return null;
        }

        Storage::disk('local')->put($storagePath, $content);

        return Storage::disk('local')->path($storagePath);
    }

    /**
     * Clean up a temporary file from storage.
     */
    public function cleanup(?string $filePath): void
    {
        if ($filePath === null) {
            return;
        }

        $storagePath = str_replace(Storage::disk('local')->path(''), '', $filePath);
        if (Storage::disk('local')->exists($storagePath)) {
            Storage::disk('local')->delete($storagePath);
        }
    }

    /**
     * Auto-detect CSV delimiter by sampling first 1KB of file.
     */
    private function detectDelimiter(string $csvPath): ?string
    {
        $content = file_get_contents($csvPath, length: 1024);
        if ($content === false) {
            return null;
        }

        $delimiters = [',', ';', "\t", '|'];
        $counts = [];

        foreach ($delimiters as $delimiter) {
            $counts[$delimiter] = substr_count($content, $delimiter);
        }

        arsort($counts);
        $detected = array_key_first($counts);

        return $counts[$detected] > 0 ? $detected : null;
    }

    /**
     * Exact row count using iterator.
     */
    private function exactRowCount(string $csvPath): int
    {
        $csvReader = $this->createReader($csvPath);

        return iterator_count($csvReader->getRecords());
    }

    /**
     * Estimate row count for large files using sampling.
     */
    private function estimateRowCount(string $csvPath, int $fileSize): int
    {
        // Get header size (first line)
        $file = fopen($csvPath, 'r');
        if ($file === false) {
            return $this->exactRowCount($csvPath);
        }

        $headerContent = fgets($file) ?: '';
        fclose($file);
        $headerBytes = strlen($headerContent);

        // Sample content after header
        $sampleContent = file_get_contents($csvPath, offset: $headerBytes, length: 8192);
        if ($sampleContent === false) {
            return $this->exactRowCount($csvPath);
        }

        $sampleLines = explode("\n", trim($sampleContent));
        $avgRowSize = strlen($sampleContent) / max(1, count($sampleLines));

        // Estimate total rows
        $dataSize = $fileSize - $headerBytes;

        return (int) ceil($dataSize / max(1, $avgRowSize));
    }
}

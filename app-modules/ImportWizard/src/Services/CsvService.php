<?php

declare(strict_types=1);

namespace Relaticle\ImportWizard\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Csv\Reader as CsvReader;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

/**
 * Unified CSV service: reader creation, row counting, file handling, Excel conversion.
 */
final class CsvService
{
    private const array EXCEL_MIME_TYPES = [
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.oasis.opendocument.spreadsheet',
    ];

    private const array EXCEL_EXTENSIONS = ['xls', 'xlsx', 'ods'];

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
     * Fast row counting with estimation for large files.
     */
    public function countRows(string $csvPath): int
    {
        $fileSize = filesize($csvPath);
        if ($fileSize === false || $fileSize < 1_048_576) {
            return iterator_count($this->createReader($csvPath)->getRecords());
        }

        // Sample-based estimation for large files
        $file = fopen($csvPath, 'r');
        if ($file === false) {
            return iterator_count($this->createReader($csvPath)->getRecords());
        }

        $headerBytes = strlen(fgets($file) ?: '');
        fclose($file);

        $sample = file_get_contents($csvPath, offset: $headerBytes, length: 8192);
        if ($sample === false) {
            return iterator_count($this->createReader($csvPath)->getRecords());
        }

        $lines = explode("\n", trim($sample));
        $avgRowSize = strlen($sample) / max(1, count($lines));

        return (int) ceil(($fileSize - $headerBytes) / max(1, $avgRowSize));
    }

    /**
     * Process uploaded file: convert Excel if needed and persist to storage.
     */
    public function processUploadedFile(TemporaryUploadedFile $file): ?string
    {
        $uploadedFile = new UploadedFile($file->getRealPath(), $file->getClientOriginalName(), $file->getMimeType());

        // Convert Excel to CSV if needed
        if ($this->isExcelFile($uploadedFile)) {
            $uploadedFile = $this->convertExcelToCsv($uploadedFile);
        }

        $content = file_get_contents($uploadedFile->getRealPath());
        if ($content === false) {
            return null;
        }

        $storagePath = 'temp-imports/'.Str::uuid()->toString().'.csv';
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
     * Check if a file is an Excel file.
     */
    private function isExcelFile(UploadedFile $file): bool
    {
        return in_array($file->getMimeType(), self::EXCEL_MIME_TYPES, true)
            || in_array(strtolower($file->getClientOriginalExtension()), self::EXCEL_EXTENSIONS, true);
    }

    /**
     * Convert an Excel file to CSV format.
     */
    private function convertExcelToCsv(UploadedFile $file): UploadedFile
    {
        $spreadsheet = IOFactory::load($file->getRealPath());

        $tempDir = Storage::disk('local')->path('temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $csvPath = $tempDir.'/'.Str::uuid()->toString().'.csv';

        $writer = new Csv($spreadsheet);
        $writer->setSheetIndex($spreadsheet->getActiveSheetIndex());
        $writer->setDelimiter(',');
        $writer->setEnclosure('"');
        $writer->setLineEnding("\n");
        $writer->setUseBOM(true);
        $writer->save($csvPath);

        $spreadsheet->disconnectWorksheets();

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        return new UploadedFile($csvPath, $originalName.'.csv', 'text/csv', null, true);
    }

    /**
     * Auto-detect CSV delimiter by sampling first 1KB.
     */
    private function detectDelimiter(string $csvPath): ?string
    {
        $content = file_get_contents($csvPath, length: 1024);
        if ($content === false) {
            return null;
        }

        $counts = [];
        foreach ([',', ';', "\t", '|'] as $d) {
            $counts[$d] = substr_count($content, $d);
        }

        arsort($counts);
        $detected = array_key_first($counts);

        return $counts[$detected] > 0 ? $detected : null;
    }
}

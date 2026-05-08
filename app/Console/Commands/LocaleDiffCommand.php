<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Symfony\Component\Finder\Finder;

final class LocaleDiffCommand extends Command
{
    protected $signature = 'locale:diff {locale : Locale code (ISO 15897, e.g. fr, pt_BR)} {--lang-path= : Override lang directory root}';

    protected $description = 'Compare keys between lang/en/ and lang/<locale>/, report missing and orphaned translations';

    public function handle(): int
    {
        $locale = (string) $this->argument('locale');
        $root = (string) ($this->option('lang-path') ?? lang_path());

        $enPath = $root.DIRECTORY_SEPARATOR.'en';
        $localePath = $root.DIRECTORY_SEPARATOR.$locale;

        if (! is_dir($enPath)) {
            $this->error("Source directory not found: {$enPath}");

            return self::FAILURE;
        }

        if (! is_dir($localePath)) {
            $this->error("Target directory not found: {$localePath}");

            return self::FAILURE;
        }

        $enKeys = $this->collectKeys($enPath);
        $localeKeys = $this->collectKeys($localePath);

        $missing = array_diff($enKeys, $localeKeys);
        $orphaned = array_diff($localeKeys, $enKeys);

        foreach ($missing as $key) {
            $this->line("Missing in {$locale}: {$key}");
        }

        foreach ($orphaned as $key) {
            $this->line("Orphaned in {$locale}: {$key}");
        }

        if ($missing === [] && $orphaned === []) {
            $this->info("Locale {$locale} matches en — no missing or orphaned keys.");

            return self::SUCCESS;
        }

        return self::FAILURE;
    }

    /**
     * @return array<int, string>
     */
    private function collectKeys(string $directory): array
    {
        $keys = [];

        foreach ((new Finder)->files()->in($directory)->name('*.php') as $file) {
            $relative = str_replace(['/', '\\'], '.', mb_substr($file->getRelativePathname(), 0, -4));
            $contents = require $file->getRealPath();

            if (! is_array($contents)) {
                continue;
            }

            foreach (Arr::dot($contents) as $key => $_value) {
                $keys[] = "{$relative}.{$key}";
            }
        }

        sort($keys);

        return array_values(array_unique($keys));
    }
}

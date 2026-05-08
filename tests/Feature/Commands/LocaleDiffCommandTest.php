<?php

declare(strict_types=1);

use App\Console\Commands\LocaleDiffCommand;
use Illuminate\Support\Facades\File;

mutates(LocaleDiffCommand::class);

beforeEach(function (): void {
    $this->fixturePath = lang_path('test-fixtures');
    File::ensureDirectoryExists($this->fixturePath.'/en');
    File::ensureDirectoryExists($this->fixturePath.'/fr');

    File::put($this->fixturePath.'/en/sample.php', "<?php return ['a' => 'A', 'b' => 'B', 'c' => 'C'];");
    File::put($this->fixturePath.'/fr/sample.php', "<?php return ['a' => 'A-fr', 'd' => 'D-fr'];");
});

afterEach(function (): void {
    File::deleteDirectory($this->fixturePath);
});

it('reports missing and orphaned keys for a locale', function (): void {
    $this->artisan('locale:diff', ['locale' => 'fr', '--lang-path' => $this->fixturePath])
        ->expectsOutputToContain('Missing in fr: sample.b')
        ->expectsOutputToContain('Missing in fr: sample.c')
        ->expectsOutputToContain('Orphaned in fr: sample.d')
        ->assertExitCode(1);
});

it('exits 0 when fr matches en exactly', function (): void {
    File::put($this->fixturePath.'/fr/sample.php', "<?php return ['a' => 'A-fr', 'b' => 'B-fr', 'c' => 'C-fr'];");

    $this->artisan('locale:diff', ['locale' => 'fr', '--lang-path' => $this->fixturePath])
        ->assertExitCode(0);
});

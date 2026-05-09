<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

it('has no untracked translation keys', function (): void {
    $projectRoot = dirname(__DIR__, 2);

    $exporter = new Process(['php', 'artisan', 'translatable:export', 'en'], $projectRoot);
    $exporter->run();
    expect($exporter->isSuccessful())->toBeTrue($exporter->getErrorOutput());

    $diff = new Process(['git', 'diff', '--exit-code', '--', 'lang/en.json'], $projectRoot);
    $diff->run();

    expect($diff->getExitCode())->toBe(
        0,
        "lang/en.json drifted. Run `php artisan translatable:export en` and commit the result. Diff:\n".$diff->getOutput()
    );
});

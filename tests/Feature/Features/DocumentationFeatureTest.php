<?php

declare(strict_types=1);

use App\Features\Documentation;
use Laravel\Pennant\Feature;

mutates(Documentation::class);

it('serves documentation pages when feature is active', function (): void {
    Feature::define(Documentation::class, true);

    $this->get('/docs')
        ->assertOk();
});

it('returns 404 for documentation pages when feature is inactive', function (): void {
    Feature::deactivate(Documentation::class);

    $this->get('/docs')
        ->assertNotFound();
});

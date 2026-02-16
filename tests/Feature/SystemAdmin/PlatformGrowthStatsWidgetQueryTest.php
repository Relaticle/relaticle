<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Relaticle\SystemAdmin\Filament\Widgets\PlatformGrowthStatsWidget;
use Relaticle\SystemAdmin\Models\SystemAdministrator;
use Tests\Helpers\QueryCounter;

beforeEach(function () {
    $this->admin = SystemAdministrator::factory()->create();
    $this->actingAs($this->admin, 'sysadmin');
    Filament::setCurrentPanel('sysadmin');
});

it('tracks query count for PlatformGrowthStatsWidget', function () {
    $counter = new QueryCounter;
    $counter->start();

    livewire(PlatformGrowthStatsWidget::class, ['pageFilters' => ['period' => '30']])
        ->assertOk();

    $counter->stop();

    $totalQueries = $counter->count();

    expect($totalQueries)->toBeGreaterThan(0);
    expect($totalQueries)->toBeLessThan(15);
});

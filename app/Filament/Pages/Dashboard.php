<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Team;
use App\Services\ChatContextService;
use App\Services\CrmDashboardService;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;

final class Dashboard extends Page
{
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Home';

    protected static ?string $title = 'Dashboard';

    protected static ?int $navigationSort = -2;

    protected string $view = 'filament.pages.dashboard';

    /** @var array<string, mixed> */
    public array $summary = [];

    /**
     * @var array<int, array{label: string, prompt: string}>
     */
    public array $suggestedPrompts = [];

    public function mount(): void
    {
        /** @var Team $team */
        $team = Filament::getTenant();

        $dashboardService = app(CrmDashboardService::class);
        $this->summary = $dashboardService->getSummary($team);

        $contextService = app(ChatContextService::class);
        $this->suggestedPrompts = $contextService->getSuggestedPrompts($contextService->getContext());
    }
}

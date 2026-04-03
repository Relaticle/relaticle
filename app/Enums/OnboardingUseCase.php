<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum OnboardingUseCase: string implements HasLabel
{
    case SalesPipeline = 'sales_pipeline';
    case Recruiting = 'recruiting';
    case Marketing = 'marketing';
    case General = 'general';

    public function getLabel(): string
    {
        return match ($this) {
            self::SalesPipeline => 'Sales Pipeline',
            self::Recruiting => 'Recruiting & Hiring',
            self::Marketing => 'Marketing Campaigns',
            self::General => 'General CRM',
        };
    }

    public function getFixtureSet(): string
    {
        return match ($this) {
            self::SalesPipeline => 'sales',
            self::Recruiting => 'recruiting',
            self::Marketing => 'marketing',
            self::General => 'general',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::SalesPipeline => 'Track deals, manage pipeline stages, and close revenue',
            self::Recruiting => 'Manage candidates, interviews, and hiring workflows',
            self::Marketing => 'Track campaigns, leads, and marketing performance',
            self::General => 'Organize contacts, companies, and tasks your way',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::SalesPipeline => 'ri-funds-line',
            self::Recruiting => 'ri-team-line',
            self::Marketing => 'ri-bar-chart-2-line',
            self::General => 'ri-contacts-book-line',
        };
    }
}

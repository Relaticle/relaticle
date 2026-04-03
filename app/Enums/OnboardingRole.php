<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum OnboardingRole: string implements HasLabel
{
    case Founder = 'founder';
    case Sales = 'sales';
    case Marketing = 'marketing';
    case Operations = 'operations';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Founder => 'Founder / CEO',
            self::Sales => 'Sales / Business Development',
            self::Marketing => 'Marketing',
            self::Operations => 'Operations / RevOps',
            self::Other => 'Other',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Founder => 'Leading the company and overseeing growth',
            self::Sales => 'Closing deals and managing customer relationships',
            self::Marketing => 'Driving awareness and generating leads',
            self::Operations => 'Optimizing processes and revenue operations',
            self::Other => 'Something else entirely',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Founder => 'ri-rocket-line',
            self::Sales => 'ri-hand-coin-line',
            self::Marketing => 'ri-megaphone-line',
            self::Operations => 'ri-settings-3-line',
            self::Other => 'ri-more-line',
        };
    }

    /**
     * @return array<string, string>
     */
    public function getUseCaseOptions(): array
    {
        return match ($this) {
            self::Founder => [
                OnboardingUseCase::SalesPipeline->value => 'Track deals & revenue',
                OnboardingUseCase::Recruiting->value => 'Manage hiring pipeline',
                OnboardingUseCase::Marketing->value => 'Run marketing campaigns',
                OnboardingUseCase::General->value => 'Manage contacts & relationships',
            ],
            self::Sales => [
                OnboardingUseCase::SalesPipeline->value => 'Sales pipeline management',
                OnboardingUseCase::General->value => 'Account & contact management',
            ],
            self::Marketing => [
                OnboardingUseCase::Marketing->value => 'Campaign & lead tracking',
                OnboardingUseCase::General->value => 'Contact management',
            ],
            self::Operations => [
                OnboardingUseCase::SalesPipeline->value => 'Pipeline & revenue operations',
                OnboardingUseCase::General->value => 'Project & contact tracking',
            ],
            self::Other => [
                OnboardingUseCase::SalesPipeline->value => 'Sales pipeline',
                OnboardingUseCase::Recruiting->value => 'Recruiting & hiring',
                OnboardingUseCase::Marketing->value => 'Marketing campaigns',
                OnboardingUseCase::General->value => 'General contact management',
            ],
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\PartnerSource;
use App\Enums\RiskBand;

/**
 * Value object bundling the four portfolio metadata fields for a Company.
 *
 * Exposed via $company->portfolio — the typed seam that MCP tools and
 * future agents reason over rather than touching raw model columns directly.
 */
final readonly class PortfolioMetadata
{
    public function __construct(
        public ?PartnerSource $partnerSource,
        public ?string $geography,
        public ?float $concentrationPercentage,
        public bool $isRecurring,
    ) {}

    /**
     * Concentration-based risk tier:
     *   null / <10%  → low
     *   10–30%       → medium
     *   ≥30%         → high
     */
    public function riskBand(): RiskBand
    {
        return match (true) {
            $this->concentrationPercentage === null => RiskBand::Low,
            $this->concentrationPercentage < 10.0 => RiskBand::Low,
            $this->concentrationPercentage < 30.0 => RiskBand::Medium,
            default => RiskBand::High,
        };
    }

    /**
     * Whether this account represents elevated concentration risk (≥30%).
     */
    public function isHighRisk(): bool
    {
        return $this->riskBand() === RiskBand::High;
    }
}

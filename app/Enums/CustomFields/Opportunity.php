<?php

declare(strict_types=1);

namespace App\Enums\CustomFields;

use App\Enums\EnumValues;

/**
 * Opportunity custom field codes
 */
enum Opportunity: string
{
    use EnumValues;
    
    case AMOUNT = 'amount';
    case CLOSE_DATE = 'close_date';
    case STAGE = 'stage';
    
    /**
     * Get stage options
     */
    public static function stageOptions(): array
    {
        return [
            'Prospecting',
            'Qualification',
            'Needs Analysis',
            'Value Proposition',
            'Id. Decision Makers',
            'Perception Analysis',
            'Proposal/Price Quote',
            'Negotiation/Review',
            'Closed Won',
            'Closed Lost',
        ];
    }
    
    /**
     * Get available options for this field
     */
    public function getOptions(): ?array
    {
        return match($this) {
            self::STAGE => self::stageOptions(),
            default => null,
        };
    }
} 
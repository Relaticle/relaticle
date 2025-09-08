<?php

declare(strict_types=1);

namespace App\Enums\CustomFields;

use App\Enums\CustomFieldType;

enum Opportunity: string
{
    use CustomFieldTrait;

    case AMOUNT = 'amount';
    case CLOSE_DATE = 'close_date';
    case STAGE = 'stage';

    /**
     * @return string[]|null
     */
    public function getOptions(): ?array
    {
        return match ($this) {
            self::STAGE => [
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
            ],
            default => null,
        };
    }

    public function getFieldType(): string
    {
        return match ($this) {
            self::AMOUNT => CustomFieldType::CURRENCY->value,
            self::CLOSE_DATE => CustomFieldType::DATE->value,
            self::STAGE => CustomFieldType::SELECT->value,
        };
    }

    public function getDisplayName(): string
    {
        return match ($this) {
            self::AMOUNT => 'Amount',
            self::CLOSE_DATE => 'Close Date',
            self::STAGE => 'Stage',
        };
    }

    public function isListToggleableHidden(): bool
    {
        return false;
    }
}

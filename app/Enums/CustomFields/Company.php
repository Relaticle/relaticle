<?php

declare(strict_types=1);

namespace App\Enums\CustomFields;

use App\Enums\CustomFieldType;

enum Company: string
{
    use CustomFieldTrait;

    /**
     * Ideal Customer Profile: Indicates whether the company is the most suitable customer for you
     */
    case ICP = 'icp';

    /**
     * Domain Name: The website domain of the company (system field)
     */
    case DOMAIN_NAME = 'domain_name';

    /**
     * LinkedIn: The LinkedIn profile URL of the company
     */
    case LINKEDIN = 'linkedin';

    public function getDisplayName(): string
    {
        return match ($this) {
            self::ICP => 'ICP',
            self::DOMAIN_NAME => 'Domain Name',
            self::LINKEDIN => 'LinkedIn', // Fixed capitalization
        };
    }

    public function getFieldType(): string
    {
        return match ($this) {
            self::ICP => CustomFieldType::TOGGLE->value,
            self::DOMAIN_NAME, self::LINKEDIN => CustomFieldType::LINK->value,
        };
    }

    public function isSystemDefined(): bool
    {
        return match ($this) {
            self::DOMAIN_NAME => true,
            default => false,
        };
    }

    public function isListToggleableHidden(): bool
    {
        return match ($this) {
            self::ICP, self::DOMAIN_NAME => false,
            default => true,
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::ICP => 'Indicates whether this company is an Ideal Customer Profile',
            self::DOMAIN_NAME => 'The website domain of the company (e.g., example.com)',
            self::LINKEDIN => 'URL to the company\'s LinkedIn profile',
        };
    }
}

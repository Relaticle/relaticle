<?php

declare(strict_types=1);

namespace App\Enums\CustomFields;

use App\Enums\EnumValues;
use Relaticle\CustomFields\Enums\CustomFieldType;

/**
 * Company custom field codes
 * 
 * This enum defines all custom fields available for the Company model.
 * Each case represents a single custom field with its configuration.
 */
enum Company: string
{
    use EnumValues;
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
    
    /**
     * Get the display name for the field
     * 
     * @return string The human-readable field name
     */
    public function getDisplayName(): string
    {
        return match($this) {
            self::ICP => 'ICP',
            self::DOMAIN_NAME => 'Domain Name',
            self::LINKEDIN => 'LinkedIn', // Fixed capitalization
        };
    }
    
    /**
     * Get the field type
     * 
     * @return CustomFieldType The type of form control to use
     */
    public function getFieldType(): CustomFieldType
    {
        return match($this) {
            self::ICP => CustomFieldType::TOGGLE,
            self::DOMAIN_NAME, self::LINKEDIN => CustomFieldType::LINK,
        };
    }
    
    /**
     * Get whether this field is system defined
     * 
     * @return bool True if the field is system defined
     */
    public function isSystemDefined(): bool
    {
        return match($this) {
            self::DOMAIN_NAME => true,
            default => false,
        };
    }
    
    /**
     * Get whether the field is hidden in list toggle
     * 
     * @return bool True if the field can be hidden in list view
     */
    public function isListToggleableHidden(): bool
    {
        return match($this) {
            self::ICP, self::DOMAIN_NAME => false,
            default => true,
        };
    }
    
    /**
     * Get field description (tooltip)
     * 
     * @return string|null The field description
     */
    public function getDescription(): ?string
    {
        return match($this) {
            self::ICP => 'Indicates whether this company is an Ideal Customer Profile',
            self::DOMAIN_NAME => 'The website domain of the company (e.g., example.com)',
            self::LINKEDIN => 'URL to the company\'s LinkedIn profile',
        };
    }
} 
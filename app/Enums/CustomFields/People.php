<?php

declare(strict_types=1);

namespace App\Enums\CustomFields;

use App\Enums\EnumValues;
use Relaticle\CustomFields\Enums\CustomFieldType;

/**
 * People custom field codes
 */
enum People: string
{
    use EnumValues;
    use CustomFieldTrait;

    case EMAILS = 'emails';
    case PHONE_NUMBER = 'phone_number';
    case JOB_TITLE = 'job_title';
    case LINKEDIN = 'linkedin';


    public function getFieldType(): CustomFieldType
    {
        return match($this) {
            self::EMAILS => CustomFieldType::TAGS_INPUT,
            self::PHONE_NUMBER, self::JOB_TITLE => CustomFieldType::TEXT,
            self::LINKEDIN => CustomFieldType::LINK,
        };
    }

    public function getDisplayName(): string
    {
        return match($this) {
            self::EMAILS => 'Emails',
            self::PHONE_NUMBER => 'Phone Number',
            self::JOB_TITLE => 'Job Title',
            self::LINKEDIN => 'LinkedIn',
        };
    }

    public function isListToggleableHidden(): bool
    {
        return match ($this) {
            self::JOB_TITLE, self::EMAILS => false,
            default => true,
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Enums\CustomFields;

use Relaticle\CustomFields\Enums\CustomFieldType;

/**
 * Note custom field codes
 */
enum Note: string
{
    use CustomFieldTrait;

    case BODY = 'body';

    public function getFieldType(): CustomFieldType
    {
        return match ($this) {
            self::BODY => CustomFieldType::RICH_EDITOR,
        };
    }

    public function getDisplayName(): string
    {
        return match ($this) {
            self::BODY => 'Body',
        };
    }

    public function isListToggleableHidden(): bool
    {
        return match ($this) {
            self::BODY => true,
        };
    }
}

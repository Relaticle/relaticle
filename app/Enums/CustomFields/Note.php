<?php

declare(strict_types=1);

namespace App\Enums\CustomFields;

use App\Enums\CustomFieldType;

/**
 * Note custom field codes
 */
enum Note: string
{
    use CustomFieldTrait;

    case BODY = 'body';

    public function getFieldType(): string
    {
        return match ($this) {
            self::BODY => CustomFieldType::RICH_EDITOR->value,
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

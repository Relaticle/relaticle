<?php

declare(strict_types=1);

namespace App\Enums\CustomFields;

use App\Enums\EnumValues;

/**
 * Note custom field codes
 */
enum Note: string
{
    use EnumValues;
    
    case BODY = 'body';
} 
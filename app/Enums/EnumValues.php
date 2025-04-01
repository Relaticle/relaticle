<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Base trait for enums with shared functionality
 */
trait EnumValues
{
    /**
     * Get all enum values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
    
    /**
     * Check if a value exists in the enum
     */
    public static function hasValue(string $value): bool
    {
        return in_array($value, self::values(), true);
    }
    
    /**
     * Get an enum case by its value
     */
    public static function fromValue(string $value): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }
        
        return null;
    }
} 
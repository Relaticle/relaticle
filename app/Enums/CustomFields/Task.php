<?php

declare(strict_types=1);

namespace App\Enums\CustomFields;

use App\Enums\EnumValues;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Enums\CustomFieldWidth;

/**
 * Task custom field codes
 * 
 * This enum defines all custom fields available for the Task model.
 * Each case represents a single custom field with its configuration.
 */
enum Task: string
{
    use EnumValues;
    use CustomFieldTrait;
    
    /**
     * Task status tracking
     */
    case STATUS = 'status';
    
    /**
     * Task priority level
     */
    case PRIORITY = 'priority';
    
    /**
     * Detailed task description
     */
    case DESCRIPTION = 'description';
    
    /**
     * Task due date and time
     */
    case DUE_DATE = 'due_date';
    
    /**
     * Get the display name for the field
     *
     * @return string The human-readable field name
     */
    public function getDisplayName(): string
    {
        return match($this) {
            self::STATUS => 'Status',
            self::PRIORITY => 'Priority',
            self::DESCRIPTION => 'Description',
            self::DUE_DATE => 'Due Date',
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
            self::STATUS, self::PRIORITY => CustomFieldType::SELECT,
            self::DESCRIPTION => CustomFieldType::RICH_EDITOR,
            self::DUE_DATE => CustomFieldType::DATE_TIME,
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
            self::STATUS, self::PRIORITY => true,
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
            self::STATUS, self::PRIORITY => false,
            default => true,
        };
    }
    
    /**
     * Get the field width
     *
     * @return CustomFieldWidth|null The width of the field or null for default width
     */
    public function getWidth(): ?CustomFieldWidth
    {
        return match($this) {
            self::STATUS, self::PRIORITY => CustomFieldWidth::_50,
            default => null,
        };
    }
    
    /**
     * Get status options
     */
    public static function statusOptions(): array
    {
        return [
            'To do',
            'In progress',
            'Done',
        ];
    }
    
    /**
     * Get priority options
     */
    public static function priorityOptions(): array
    {
        return [
            'Low',
            'Medium',
            'High',
        ];
    }
    
    /**
     * Get available options for this field
     * 
     * @return array<int|string, string>|null Array of options for select/multi-select fields or null if not applicable
     */
    public function getOptions(): ?array
    {
        return match($this) {
            self::STATUS => self::statusOptions(),
            self::PRIORITY => self::priorityOptions(),
            default => null,
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
            self::STATUS => 'Current status of the task',
            self::PRIORITY => 'Priority level for this task',
            self::DESCRIPTION => 'Detailed description of what needs to be done',
            self::DUE_DATE => 'When this task needs to be completed by',
        };
    }
} 
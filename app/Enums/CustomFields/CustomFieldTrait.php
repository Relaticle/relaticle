<?php

declare(strict_types=1);

namespace App\Enums\CustomFields;

use Illuminate\Support\Str;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Enums\CustomFieldWidth;

/**
 * Trait for custom field enums to provide configuration data
 *
 * This trait provides a standardized interface for custom field enums
 * and default implementations for optional methods.
 */
trait CustomFieldTrait
{
    /**
     * Get the field type
     *
     * @return CustomFieldType The type of form field to use
     */
    abstract public function getFieldType(): CustomFieldType;

    /**
     * Get the display name for the field
     *
     * @return string The human-readable name of the field
     */
    public function getDisplayName(): string
    {
        return Str::title($this->name);
    }

    /**
     * Get whether this field is system defined
     *
     * System-defined fields cannot be deleted by users, only deactivated.
     *
     * @return bool True if the field is system defined
     */
    public function isSystemDefined(): bool
    {
        return true;
    }

    /**
     * Get whether the field is hidden in list toggle
     *
     * Fields that aren't toggleable hidden are always shown in lists.
     *
     * @return bool True if the field can be hidden in list view
     */
    public function isListToggleableHidden(): bool
    {
        return true;
    }

    /**
     * Get the field width
     *
     * @return CustomFieldWidth The width of the field or null for default width
     */
    public function getWidth(): CustomFieldWidth
    {
        return CustomFieldWidth::_100;
    }

    /**
     * Get options for select fields
     *
     * @return array<int|string, string>|null Array of options for select/multi-select fields or null if not applicable
     */
    public function getOptions(): ?array
    {
        return null;
    }

    /**
     * Get field description (tooltip)
     *
     * @return string|null The field description or null if not provided
     */
    public function getDescription(): ?string
    {
        return null;
    }

    /**
     * Get complete field configuration
     *
     * @return array{
     *     name: string,
     *     type: CustomFieldType,
     *     systemDefined: bool,
     *     listToggleableHidden: bool,
     *     width: CustomFieldWidth|null,
     *     options: array<int|string, string>|null,
     *     description: string|null
     * } The complete field configuration
     */
    public function getConfiguration(): array
    {
        return [
            'name' => $this->getDisplayName(),
            'type' => $this->getFieldType(),
            'systemDefined' => $this->isSystemDefined(),
            'listToggleableHidden' => $this->isListToggleableHidden(),
            'width' => $this->getWidth(),
            'options' => $this->getOptions(),
            'description' => $this->getDescription(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Enums;

use Filament\Support\Contracts\HasLabel;

enum AttributeType: string implements HasLabel
{
    case TEXT = 'text';
    case TEXTAREA = 'textarea';
    case PRICE = 'price';
    case DATE = 'date';
    case DATETIME = 'date_time';
    case TOGGLE = 'boolean';
    case SELECT = 'select';
    case MULTISELECT = 'multiselect';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::TEXT->value => 'Text',
            self::TEXTAREA->value => 'Textarea',
            self::PRICE->value => 'Price',
            self::DATE->value => 'Date',
            self::DATETIME->value => 'Date and Time',
            self::TOGGLE->value => 'Toggle',
            self::SELECT->value => 'Select',
            self::MULTISELECT->value => 'Multiselect',
        ];
    }

    public function getLabel(): ?string
    {
        return self::options()[$this->value];
    }

    /**
     * @return array<int, AttributeValidationRule>
     */
    public function allowedValidationRules(): array
    {
        return match ($this) {
            self::TEXT => [
                AttributeValidationRule::REQUIRED,
                AttributeValidationRule::MIN,
                AttributeValidationRule::MAX,
                AttributeValidationRule::BETWEEN,
                AttributeValidationRule::REGEX,
                AttributeValidationRule::ALPHA,
                AttributeValidationRule::ALPHA_NUM,
                AttributeValidationRule::ALPHA_DASH,
                AttributeValidationRule::STRING,
            ],
            self::TEXTAREA => [
                AttributeValidationRule::REQUIRED,
                AttributeValidationRule::MIN,
                AttributeValidationRule::MAX,
                AttributeValidationRule::BETWEEN,
                AttributeValidationRule::STRING,
            ],
            self::PRICE => [
                AttributeValidationRule::REQUIRED,
                AttributeValidationRule::NUMERIC,
                AttributeValidationRule::MIN,
                AttributeValidationRule::MAX,
                AttributeValidationRule::BETWEEN,
                AttributeValidationRule::DECIMAL,
            ],
            self::DATE, self::DATETIME => [
                AttributeValidationRule::REQUIRED,
                AttributeValidationRule::DATE,
                AttributeValidationRule::AFTER,
                AttributeValidationRule::AFTER_OR_EQUAL,
                AttributeValidationRule::BEFORE,
                AttributeValidationRule::BEFORE_OR_EQUAL,
                AttributeValidationRule::DATE_FORMAT,
            ],
            self::TOGGLE => [
                AttributeValidationRule::REQUIRED,
                AttributeValidationRule::BOOLEAN,
            ],
            self::SELECT => [
                AttributeValidationRule::REQUIRED,
                AttributeValidationRule::IN,
            ],
            self::MULTISELECT => [
                AttributeValidationRule::REQUIRED,
                AttributeValidationRule::ARRAY,
                AttributeValidationRule::MIN,
                AttributeValidationRule::MAX,
                AttributeValidationRule::BETWEEN,
                AttributeValidationRule::IN,
            ],
        };
    }
}

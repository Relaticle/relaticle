<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Enums;

use Filament\Support\Contracts\HasLabel;

enum AttributeTypeEnum: string implements HasLabel
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
}

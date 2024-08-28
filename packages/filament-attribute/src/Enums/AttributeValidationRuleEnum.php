<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Enums;

use Filament\Support\Contracts\HasLabel;

enum AttributeValidationRuleEnum: string implements HasLabel
{
    case REQUIRED = 'required';
    case REQUIRED_WITH = 'required_with';
    case ENDS_WITH = 'ends_with';
    case MIN = 'min';
    case MAX = 'max';
    case BETWEEN = 'between';
    case REGEX = 'regex';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_column(self::cases(), 'name')
        );
    }

    public function hasParameter(): bool
    {
        return match($this) {
            self::REQUIRED_WITH, self::ENDS_WITH, self::MIN, self::MAX, self::BETWEEN, self::REGEX => true,
            default => false
        };
    }

    public function getLabel(): string
    {
        return match($this) {
            self::REQUIRED => 'Required',
            self::REQUIRED_WITH => 'Required with',
            self::ENDS_WITH => 'Ends with',
            self::MIN => 'Minimum',
            self::MAX => 'Maximum',
            self::BETWEEN => 'Between',
            self::REGEX => 'Regular Expression',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::REQUIRED => 'The field must be filled',
            self::REQUIRED_WITH => 'The field is required when any of the other specified fields are present',
            self::ENDS_WITH => 'The field must end with one of the given values',
            self::MIN => 'The field must have a minimum value',
            self::MAX => 'The field must have a maximum value',
            self::BETWEEN => 'The field must have a value between the given range',
            self::REGEX => 'The field must match the given regular expression pattern',
        };
    }

    public static function hasParameterForRule(?string $rule): bool
    {
        if ($rule === null) {
            return false;
        }
        return self::tryFrom($rule)?->hasParameter() ?? false;
    }

    public static function getDescriptionForRule(?string $rule): string
    {
        if ($rule === null) {
            return 'Select a rule to see its description.';
        }
        return self::tryFrom($rule)?->getDescription() ?? 'Select a rule to see its description.';
    }

    public static function getLabelForRule(?string $rule, array $parameters = []): string
    {
        if ($rule === null) {
            return '';
        }
        $enum = self::tryFrom($rule);
        if (!$enum) {
            return '';
        }

        $label = $enum->getLabel();
        if (!empty($parameters)) {
            $label .= ' (' . implode(', ', array_column($parameters, 'value')) . ')';
        }
        return $label;
    }
}

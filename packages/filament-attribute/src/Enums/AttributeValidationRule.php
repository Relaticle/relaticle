<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Enums;

use Filament\Support\Contracts\HasLabel;

enum AttributeValidationRule: string implements HasLabel
{
    case ACCEPTED = 'accepted';
    case ACCEPTED_IF = 'accepted_if';
    case ACTIVE_URL = 'active_url';
    case AFTER = 'after';
    case AFTER_OR_EQUAL = 'after_or_equal';
    case ALPHA = 'alpha';
    case ALPHA_DASH = 'alpha_dash';
    case ALPHA_NUM = 'alpha_num';
    case ARRAY = 'array';
    case ASCII = 'ascii';
    case BEFORE = 'before';
    case BEFORE_OR_EQUAL = 'before_or_equal';
    case BETWEEN = 'between';
    case BOOLEAN = 'boolean';
    case CONFIRMED = 'confirmed';
    case CURRENT_PASSWORD = 'current_password';
    case DATE = 'date';
    case DATE_EQUALS = 'date_equals';
    case DATE_FORMAT = 'date_format';
    case DECIMAL = 'decimal';
    case DECLINED = 'declined';
    case DECLINED_IF = 'declined_if';
    case DIFFERENT = 'different';
    case DIGITS = 'digits';
    case DIGITS_BETWEEN = 'digits_between';
    case DIMENSIONS = 'dimensions';
    case DISTINCT = 'distinct';
    case DOESNT_START_WITH = 'doesnt_start_with';
    case DOESNT_END_WITH = 'doesnt_end_with';
    case EMAIL = 'email';
    case ENDS_WITH = 'ends_with';
    case ENUM = 'enum';
    case EXCLUDE = 'exclude';
    case EXCLUDE_IF = 'exclude_if';
    case EXCLUDE_UNLESS = 'exclude_unless';
    case EXISTS = 'exists';
    case FILE = 'file';
    case FILLED = 'filled';
    case GT = 'gt';
    case GTE = 'gte';
    case IMAGE = 'image';
    case IN = 'in';
    case IN_ARRAY = 'in_array';
    case INTEGER = 'integer';
    case IP = 'ip';
    case IPV4 = 'ipv4';
    case IPV6 = 'ipv6';
    case JSON = 'json';
    case LT = 'lt';
    case LTE = 'lte';
    case MAC_ADDRESS = 'mac_address';
    case MAX = 'max';
    case MAX_DIGITS = 'max_digits';
    case MIMES = 'mimes';
    case MIMETYPES = 'mimetypes';
    case MIN = 'min';
    case MIN_DIGITS = 'min_digits';
    case MULTIPLE_OF = 'multiple_of';
    case NOT_IN = 'not_in';
    case NOT_REGEX = 'not_regex';
    case NUMERIC = 'numeric';
    case PASSWORD = 'password';
    case PRESENT = 'present';
    case PROHIBITED = 'prohibited';
    case PROHIBITED_IF = 'prohibited_if';
    case PROHIBITED_UNLESS = 'prohibited_unless';
    case PROHIBITS = 'prohibits';
    case REGEX = 'regex';
    case REQUIRED = 'required';
    case REQUIRED_IF = 'required_if';
    case REQUIRED_UNLESS = 'required_unless';
    case REQUIRED_WITH = 'required_with';
    case REQUIRED_WITH_ALL = 'required_with_all';
    case REQUIRED_WITHOUT = 'required_without';
    case REQUIRED_WITHOUT_ALL = 'required_without_all';
    case SAME = 'same';
    case SIZE = 'size';
    case STARTS_WITH = 'starts_with';
    case STRING = 'string';
    case TIMEZONE = 'timezone';
    case UNIQUE = 'unique';
    case UPPERCASE = 'uppercase';
    case URL = 'url';
    case UUID = 'uuid';

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
        return match ($this) {
            self::ACCEPTED_IF, self::AFTER, self::AFTER_OR_EQUAL, self::BEFORE, self::BEFORE_OR_EQUAL,
            self::BETWEEN, self::DATE_FORMAT, self::DECIMAL, self::DECLINED_IF, self::DIFFERENT,
            self::DIGITS, self::DIGITS_BETWEEN, self::DIMENSIONS, self::ENDS_WITH, self::EXISTS,
            self::GT, self::GTE, self::IN, self::LT, self::LTE, self::MAX, self::MAX_DIGITS,
            self::MIMES, self::MIMETYPES, self::MIN, self::MIN_DIGITS, self::MULTIPLE_OF,
            self::NOT_IN, self::REGEX, self::REQUIRED_IF, self::REQUIRED_UNLESS, self::REQUIRED_WITH,
            self::REQUIRED_WITH_ALL, self::REQUIRED_WITHOUT, self::REQUIRED_WITHOUT_ALL,
            self::SAME, self::SIZE, self::STARTS_WITH, self::UNIQUE => true,
            default => false
        };
    }

    public function getLabel(): string
    {
        return ucfirst(str_replace('_', ' ', $this->name));
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::ACCEPTED => 'The field must be accepted.',
            self::ACCEPTED_IF => 'The field must be accepted when another field has a given value.',
            self::ACTIVE_URL => 'The field must be a valid URL and must have a valid A or AAAA record',
            self::AFTER => 'The field must be a date after the given date.',
            self::AFTER_OR_EQUAL => 'The field must be a date after or equal to the given date.',
            self::ALPHA => 'The field must contain only alphabetic characters.',
            self::ALPHA_DASH => 'The field must contain only alpha-numeric characters, dashes, and underscores.',
            self::ALPHA_NUM => 'The field must contain only alpha-numeric characters.',
            self::ARRAY => 'The field must be an array.',
            self::ASCII => 'The field must contain only ASCII characters.',
            self::BEFORE => 'The field must be a date before the given date.',
            self::BEFORE_OR_EQUAL => 'The field must be a date before or equal to the given date.',
            self::BETWEEN => 'The field must be between the given values.',
            self::BOOLEAN => 'The field must be a boolean value.',
            self::CONFIRMED => 'The field must have a matching confirmation field.',
            self::CURRENT_PASSWORD => 'The field must match the user\'s current password.',
            self::DATE => 'The field must be a valid date.',
            self::DATE_EQUALS => 'The field must be a date equal to the given date.',
            self::DATE_FORMAT => 'The field must match the given date format.',
            self::DECIMAL => 'The field must have the specified number of decimal places.',
            self::DECLINED => 'The field must be declined.',
            self::DECLINED_IF => 'The field must be declined when another field has a given value.',
            self::DIFFERENT => 'The field must have a different value than the specified field.',
            self::DIGITS => 'The field must be numeric and have an exact length.',
            self::DIGITS_BETWEEN => 'The field must be numeric and have a length between the given values.',
            self::DIMENSIONS => 'The field must be an image that meets the dimension constraints.',
            self::DISTINCT => 'The field must not have any duplicate values.',
            self::DOESNT_START_WITH => 'The field must not start with one of the given values.',
            self::DOESNT_END_WITH => 'The field must not end with one of the given values.',
            self::EMAIL => 'The field must be a valid email address.',
            self::ENDS_WITH => 'The field must end with one of the given values.',
            self::ENUM => 'The field must be a valid enum value.',
            self::EXCLUDE => 'The field must be excluded from the data.',
            self::EXCLUDE_IF => 'The field must be excluded if another field has a given value.',
            self::EXCLUDE_UNLESS => 'The field must be excluded unless another field has a given value.',
            self::EXISTS => 'The field must exist in the database.',
            self::FILE => 'The field must be a successfully uploaded file.',
            self::FILLED => 'The field must not be empty when present.',
            self::GT => 'The field must be greater than the given field.',
            self::GTE => 'The field must be greater than or equal to the given field.',
            self::IMAGE => 'The field must be an image.',
            self::IN => 'The field must be included in the given list of values.',
            self::IN_ARRAY => 'The field must exist in another field\'s values.',
            self::INTEGER => 'The field must be an integer.',
            self::IP => 'The field must be a valid IP address.',
            self::IPV4 => 'The field must be a valid IPv4 address.',
            self::IPV6 => 'The field must be a valid IPv6 address.',
            self::JSON => 'The field must be a valid JSON string.',
            self::LT => 'The field must be less than the given field.',
            self::LTE => 'The field must be less than or equal to the given field.',
            self::MAC_ADDRESS => 'The field must be a valid MAC address.',
            self::MAX => 'The field must not be greater than the given value.',
            self::MAX_DIGITS => 'The field must not have more than the specified number of digits.',
            self::MIMES => 'The file must be of the specified MIME types.',
            self::MIMETYPES => 'The file must match one of the given MIME types.',
            self::MIN => 'The field must be at least the given value.',
            self::MIN_DIGITS => 'The field must have at least the specified number of digits.',
            self::MULTIPLE_OF => 'The field must be a multiple of the given value.',
            self::NOT_IN => 'The field must not be included in the given list of values.',
            self::NOT_REGEX => 'The field must not match the given regular expression.',
            self::NUMERIC => 'The field must be numeric.',
            self::PASSWORD => 'The field must match the user\'s password.',
            self::PRESENT => 'The field must be present in the input data.',
            self::PROHIBITED => 'The field is prohibited.',
            self::PROHIBITED_IF => 'The field is prohibited when another field has a given value.',
            self::PROHIBITED_UNLESS => 'The field is prohibited unless another field has a given value.',
            self::PROHIBITS => 'The field prohibits other fields from being present.',
            self::REGEX => 'The field must match the given regular expression.',
            self::REQUIRED => 'The field is required.',
            self::REQUIRED_IF => 'The field is required when another field has a given value.',
            self::REQUIRED_UNLESS => 'The field is required unless another field has a given value.',
            self::REQUIRED_WITH => 'The field is required when any of the other specified fields are present.',
            self::REQUIRED_WITH_ALL => 'The field is required when all of the other specified fields are present.',
            self::REQUIRED_WITHOUT => 'The field is required when any of the other specified fields are not present.',
            self::REQUIRED_WITHOUT_ALL => 'The field is required when all of the other specified fields are not present.',
            self::SAME => 'The field value must match the specified field\'s value.',
            self::SIZE => 'The field must have the specified size.',
            self::STARTS_WITH => 'The field must start with one of the given values.',
            self::STRING => 'The field must be a string.',
            self::TIMEZONE => 'The field must be a valid timezone identifier.',
            self::UNIQUE => 'The field must be unique in the specified database table.',
            self::UPPERCASE => 'The field must be uppercase.',
            self::URL => 'The field must be a valid URL.',
            self::UUID => 'The field must be a valid UUID.',
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

    /**
     * Get the label for a given validation rule.
     *
     * @param  string  $rule  The validation rule.
     * @param  array<string, string>  $parameters  The parameters to be passed to the validation rule.
     * @return string The label for the given validation rule.
     */
    public static function getLabelForRule(string $rule, array $parameters = []): string
    {
        $enum = self::tryFrom($rule);

        if (! $enum instanceof AttributeValidationRule) {
            return '';
        }

        $label = $enum->getLabel();
        if ($parameters !== []) {
            $label .= ' ('.implode(', ', array_column($parameters, 'value')).')';
        }

        return $label;
    }
}

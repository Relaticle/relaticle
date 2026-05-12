<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Converts ISO 3166-1 alpha-2 country codes to flag emojis and human-readable names.
 */
final readonly class CountryFlag
{
    /** @var array<string, string> */
    private const array COUNTRIES = [
        'AU' => 'Australia',
        'AT' => 'Austria',
        'BE' => 'Belgium',
        'BR' => 'Brazil',
        'CA' => 'Canada',
        'CL' => 'Chile',
        'CN' => 'China',
        'CO' => 'Colombia',
        'CZ' => 'Czech Republic',
        'DK' => 'Denmark',
        'EG' => 'Egypt',
        'FI' => 'Finland',
        'FR' => 'France',
        'DE' => 'Germany',
        'GH' => 'Ghana',
        'HK' => 'Hong Kong',
        'HU' => 'Hungary',
        'IN' => 'India',
        'ID' => 'Indonesia',
        'IE' => 'Ireland',
        'IL' => 'Israel',
        'IT' => 'Italy',
        'JP' => 'Japan',
        'KE' => 'Kenya',
        'MX' => 'Mexico',
        'NL' => 'Netherlands',
        'NZ' => 'New Zealand',
        'NG' => 'Nigeria',
        'NO' => 'Norway',
        'PK' => 'Pakistan',
        'PH' => 'Philippines',
        'PL' => 'Poland',
        'PT' => 'Portugal',
        'RO' => 'Romania',
        'SA' => 'Saudi Arabia',
        'SG' => 'Singapore',
        'ZA' => 'South Africa',
        'KR' => 'South Korea',
        'ES' => 'Spain',
        'SE' => 'Sweden',
        'CH' => 'Switzerland',
        'TW' => 'Taiwan',
        'TH' => 'Thailand',
        'TR' => 'Turkey',
        'UA' => 'Ukraine',
        'AE' => 'United Arab Emirates',
        'GB' => 'United Kingdom',
        'US' => 'United States',
        'VN' => 'Vietnam',
        'ZW' => 'Zimbabwe',
    ];

    /**
     * Converts an ISO 3166-1 alpha-2 country code to its flag emoji.
     * Works by mapping each letter to its Unicode Regional Indicator Symbol.
     */
    public static function emoji(string $code): string
    {
        $offset = 0x1F1A5;
        $code = strtoupper($code);

        return mb_chr($offset + ord($code[0])).mb_chr($offset + ord($code[1]));
    }

    /**
     * Returns the human-readable country name for a code, falling back to the code itself.
     */
    public static function name(string $code): string
    {
        return self::COUNTRIES[strtoupper($code)] ?? strtoupper($code);
    }

    /**
     * Returns Select-compatible options: code → "🇺🇸 United States".
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $result = [];
        foreach (self::COUNTRIES as $code => $name) {
            $result[$code] = self::emoji($code).' '.$name;
        }

        return $result;
    }

    /**
     * Returns the full country list as code → name (no emoji).
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return self::COUNTRIES;
    }
}

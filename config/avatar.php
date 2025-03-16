<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Avatar Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the avatar generation service.
    | The service generates SVG avatars for users based on their initials
    | with configurable colors and caching options.
    |
    */

    // Default background color for avatars (hex format)
    'default_background_color' => env('AVATAR_BACKGROUND_COLOR', '#ede9fe'),

    // Default text color for avatars (hex format)
    'default_text_color' => env('AVATAR_TEXT_COLOR', '#5b21b6'),

    // Time to live for avatar cache (in seconds)
    'cache_ttl' => env('AVATAR_CACHE_TTL', 604800), // Default: 1 week (corrected from 1 second)

    // Available background colors for random/deterministic selection
    // Only used if background_color is set to null
    'background_colors' => [
        '#E53E3E', // Red
        '#DD6B20', // Orange
        '#D69E2E', // Yellow
        '#38A169', // Green
        '#3182CE', // Blue
        '#805AD5', // Purple
        '#D53F8C', // Pink
        '#718096', // Gray
        '#F87171', // Red
        '#FBBF24', // Yellow
        '#34D399', // Green
        '#3B82F6', // Blue
        '#A78BFA', // Purple
        '#F472B6', // Pink
        '#6B7280', // Gray
    ],

    // Minimum contrast ratio for text/background (WCAG AA requires 4.5:1)
    'minimum_contrast_ratio' => 4.5,
];

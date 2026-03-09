<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'enabled' => env('GOOGLE_ENABLED', false),
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'github' => [
        'enabled' => env('GITHUB_ENABLED', false),
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => '/auth/callback/github',
    ],
    'oidc' => [
        'enabled' => env('OIDC_ENABLED', false),
        'display_name' => env('OIDC_DISPLAY_NAME', 'OIDC'),
        'icon' => env('OIDC_ICON', 'oidc'),
        'base_url' => env('OIDC_ISSUER'),

        'client_id' => env('OIDC_CLIENT_ID'),
        'client_secret' => env('OIDC_CLIENT_SECRET'),

        'redirect' => env('OIDC_REDIRECT_URI', default: '/auth/callback/oidc'),

        // Optional: Enable JWT signature verification (default: false)
        'verify_jwt' => env('OIDC_VERIFY_JWT', false),
        // Optional: Provide a specific public key for JWT verification
        // If not provided, the key will be fetched from the OIDC provider's JWKS endpoint
        'jwt_public_key' => env('OIDC_JWT_PUBLIC_KEY', default: null),
        'scopes' => env('OIDC_SCOPES', 'openid profile email'),
    ],
    'fathom' => [
        'site_id' => env('FATHOM_ANALYTICS_SITE_ID'),
    ],

    'discord' => [
        'invite_url' => env('DISCORD_INVITE_URL'),
    ],

    'anthropic' => [
        'summary_model' => env('ANTHROPIC_SUMMARY_MODEL', 'claude-haiku-4-5'),
    ],
];

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
        'enabled' => env('GOOGLE_ENABLED', true),
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'github' => [
        'enabled' => env('GITHUB_ENABLED', true),
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => '/auth/callback/github',
    ],

    'keycloak' => [
        'enabled' => env('KEYCLOAK_ENABLED', false),
        'display_name' => env('KEYCLOAK_DISPLAY_NAME', 'Keycloak'),
        'client_id' => env('KEYCLOAK_CLIENT_ID'),
        'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),
        'base_url' => env('KEYCLOAK_BASE_URL'),
        'realms' => env('KEYCLOAK_REALM', 'master'),
        'redirect' => env('KEYCLOAK_REDIRECT_URI', '/auth/callback/keycloak'),
    ],

    'okta' => [
        'enabled' => env('OKTA_ENABLED', false),
        'client_id' => env('OKTA_CLIENT_ID'),
        'client_secret' => env('OKTA_CLIENT_SECRET'),
        'base_url' => env('OKTA_BASE_URL'),
        'redirect' => env('OKTA_REDIRECT_URI', '/auth/callback/okta'),
    ],

    'azure' => [
        'enabled' => env('AZURE_ENABLED', false),
        'client_id' => env('AZURE_CLIENT_ID'),
        'client_secret' => env('AZURE_CLIENT_SECRET'),
        'tenant' => env('AZURE_TENANT', 'common'),
        'redirect' => env('AZURE_REDIRECT_URI', '/auth/callback/azure'),
    ],

    'authentik' => [
        'enabled' => env('AUTHENTIK_ENABLED', false),
        'client_id' => env('AUTHENTIK_CLIENT_ID'),
        'client_secret' => env('AUTHENTIK_CLIENT_SECRET'),
        'base_url' => env('AUTHENTIK_BASE_URL'),
        'redirect' => env('AUTHENTIK_REDIRECT_URI', '/auth/callback/authentik'),
    ],

    'auth0' => [
        'enabled' => env('AUTH0_ENABLED', false),
        'client_id' => env('AUTH0_CLIENT_ID'),
        'client_secret' => env('AUTH0_CLIENT_SECRET'),
        'base_url' => env('AUTH0_BASE_URL'),
        'redirect' => env('AUTH0_REDIRECT_URI', '/auth/callback/auth0'),
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

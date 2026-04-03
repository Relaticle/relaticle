<?php

declare(strict_types=1);

namespace App\Enums;

enum SubscriberTagEnum: string
{
    case VERIFIED = 'verified';

    // Event-driven tags (additive, never removed)
    case HAS_CRM_DATA = 'has-crm-data';
    case HAS_API_TOKEN = 'has-api-token';
    case HAS_AI_USAGE = 'has-ai-usage';
    case HAS_TEAM_MEMBERS = 'has-team-members';

    // Signup source tags (set once at registration)
    case SIGNUP_SOURCE_ORGANIC = 'signup-source:organic';
    case SIGNUP_SOURCE_GOOGLE = 'signup-source:google';

    // Time-decay recency tags (managed by scheduled command)
    case ACTIVE_7D = 'active-7d';
    case ACTIVE_30D = 'active-30d';
    case DORMANT = 'dormant';

    /** @return list<self> */
    public static function recencyTags(): array
    {
        return [
            self::ACTIVE_7D,
            self::ACTIVE_30D,
            self::DORMANT,
        ];
    }
}

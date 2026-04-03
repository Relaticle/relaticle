<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum OnboardingReferralSource: string implements HasLabel
{
    case Google = 'google';
    case LinkedIn = 'linkedin';
    case X = 'x';
    case Reddit = 'reddit';
    case YouTube = 'youtube';
    case FriendsCoworker = 'friends_coworker';
    case Newsletter = 'newsletter';
    case AI = 'ai';
    case Podcast = 'podcast';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Google => 'Google',
            self::LinkedIn => 'LinkedIn',
            self::X => 'X (Twitter)',
            self::Reddit => 'Reddit',
            self::YouTube => 'YouTube',
            self::FriendsCoworker => 'Friends / Coworker',
            self::Newsletter => 'Newsletter',
            self::AI => 'AI (ChatGPT, etc.)',
            self::Podcast => 'Podcast',
            self::Other => 'Other',
        };
    }
}

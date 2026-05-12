<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PartnerSource: string implements HasColor, HasIcon, HasLabel
{
    case Direct = 'direct';
    case ReferralPartner = 'referral_partner';
    case ChannelPartner = 'channel_partner';
    case Reseller = 'reseller';
    case MarketingInbound = 'marketing_inbound';
    case Event = 'event';

    public function getLabel(): string
    {
        return match ($this) {
            self::Direct => 'Direct',
            self::ReferralPartner => 'Referral Partner',
            self::ChannelPartner => 'Channel Partner',
            self::Reseller => 'Reseller',
            self::MarketingInbound => 'Marketing Inbound',
            self::Event => 'Event',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Direct => 'info',
            self::ReferralPartner => 'success',
            self::ChannelPartner => 'warning',
            self::Reseller => 'purple',
            self::MarketingInbound => 'danger',
            self::Event => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Direct => 'heroicon-o-user',
            self::ReferralPartner => 'heroicon-o-users',
            self::ChannelPartner => 'heroicon-o-building-office-2',
            self::Reseller => 'heroicon-o-shopping-bag',
            self::MarketingInbound => 'heroicon-o-megaphone',
            self::Event => 'heroicon-o-calendar',
        };
    }
}

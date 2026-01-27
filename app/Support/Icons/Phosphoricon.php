<?php

declare(strict_types=1);

namespace App\Support\Icons;

use Filament\Support\Contracts\ScalableIcon;
use Filament\Support\Enums\IconSize;

enum Phosphoricon: string implements ScalableIcon
{
    // Navigation & Layout
    case House = 'house';
    case List = 'list';
    case ListBullets = 'list-bullets';
    case Columns = 'columns';
    case GridFour = 'grid-four';
    case Rows = 'rows';
    case Queue = 'queue';
    case Table = 'table';

    // Arrows & Navigation
    case ArrowRight = 'arrow-right';
    case ArrowLeft = 'arrow-left';
    case ArrowDown = 'arrow-down';
    case ArrowCircleRight = 'arrow-circle-right';
    case ArrowSquareOut = 'arrow-square-out';
    case ArrowBendUpLeft = 'arrow-bend-up-left';
    case ArrowUUpLeft = 'arrow-u-up-left';
    case ArrowsClockwise = 'arrows-clockwise';
    case ArrowsDownUp = 'arrows-down-up';
    case ArrowFatLinesDown = 'arrow-fat-lines-down';
    case ArrowFatLinesUp = 'arrow-fat-lines-up';
    case ChartLineUp = 'chart-line-up';
    case ChartLineDown = 'chart-line-down';
    case CaretDown = 'caret-down';
    case CaretRight = 'caret-right';
    case SignOut = 'sign-out';

    // Actions
    case Plus = 'plus';
    case X = 'x';
    case XCircle = 'x-circle';
    case Check = 'check';
    case CheckCircle = 'check-circle';
    case Checks = 'checks';
    case Trash = 'trash';
    case Pencil = 'pencil';
    case PencilSimple = 'pencil-simple';
    case NotePencil = 'note-pencil';
    case Eye = 'eye';
    case Upload = 'upload';
    case MagnifyingGlass = 'magnifying-glass';
    case Funnel = 'funnel';
    case Link = 'link';

    // Documents & Files
    case File = 'file';
    case FileText = 'file-text';
    case Files = 'files';
    case Note = 'note';
    case Notebook = 'notebook';
    case Clipboard = 'clipboard';
    case ClipboardText = 'clipboard-text';
    case BookOpen = 'book-open';

    // Business & Commerce
    case Buildings = 'buildings';
    case ShoppingCart = 'shopping-cart';
    case CurrencyDollar = 'currency-dollar';
    case CurrencyCircleDollar = 'currency-circle-dollar';
    case Trophy = 'trophy';
    case Bank = 'bank';

    // Users & People
    case User = 'user';
    case UserCircle = 'user-circle';
    case Users = 'users';
    case UsersThree = 'users-three';

    // Time & Calendar
    case Clock = 'clock';
    case Calendar = 'calendar';
    case CalendarBlank = 'calendar-blank';

    // Status & Indicators
    case Warning = 'warning';
    case ShieldCheck = 'shield-check';
    case SealCheck = 'seal-check';
    case Prohibit = 'prohibit';
    case Flag = 'flag';
    case Sparkle = 'sparkle';

    // Settings & Tools
    case Gear = 'gear';
    case GearSix = 'gear-six';
    case Key = 'key';
    case Code = 'code';
    case Hash = 'hash';
    case Translate = 'translate';

    // Devices & Interface
    case Desktop = 'desktop';
    case DeviceMobile = 'device-mobile';
    case Globe = 'globe';
    case Sun = 'sun';
    case Moon = 'moon';
    case Spinner = 'spinner';
    case SquaresFour = 'squares-four';
    case DotsThree = 'dots-three';

    // Communication
    case Envelope = 'envelope';
    case RocketLaunch = 'rocket-launch';
    case HandPointing = 'hand-pointing';

    // Duotone Variants (for navigation)
    case DuotoneHouse = 'd-house';
    case DuotoneBuildings = 'd-buildings';
    case DuotoneUser = 'd-user';
    case DuotoneUserCircle = 'd-user-circle';
    case DuotoneUsers = 'd-users';
    case DuotoneTrophy = 'd-trophy';
    case DuotoneCheckCircle = 'd-check-circle';
    case DuotoneFileText = 'd-file-text';
    case DuotoneNotebook = 'd-notebook';
    case DuotoneKey = 'd-key';
    case DuotoneColumns = 'd-columns';
    case DuotoneShieldCheck = 'd-shield-check';
    case DuotoneGearSix = 'd-gear-six';
    case DuotoneShoppingCart = 'd-shopping-cart';
    case DuotoneGlobe = 'd-globe';
    case DuotoneUpload = 'd-upload';
    case DuotoneClipboardText = 'd-clipboard-text';
    case DuotoneCurrencyDollar = 'd-currency-dollar';

    public function getIconForSize(IconSize $size): string
    {
        if (str_starts_with($this->value, 'd-')) {
            $iconName = substr($this->value, 2);

            return "phosphor-d-{$iconName}";
        }

        return match ($size) {
            IconSize::ExtraSmall, IconSize::Small => "phosphor-o-{$this->value}",
            IconSize::Medium => "phosphor-o-{$this->value}",
            IconSize::Large, IconSize::ExtraLarge, IconSize::TwoExtraLarge => "phosphor-b-{$this->value}",
        };
    }
}

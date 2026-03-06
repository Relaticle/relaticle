/**
 * Centralized icon library using Lucide icons.
 *
 * All workflow builder icons are defined here to avoid inline SVG duplication.
 * Icons are imported from the `lucide` package and rendered as HTML strings.
 */
import {
    Zap,
    Play,
    Activity,
    Clock,
    Repeat,
    Square,
    FileInput,
    Mail,
    Webhook,
    Globe,
    FilePlus,
    FilePen,
    FileSearch,
    FileX,
    MessageSquare,
    FileText,
    Tag,
    Calculator,
    BarChart3,
    ClockArrowUp,
    Dices,
    Megaphone,
    PartyPopper,
    Braces,
    Sparkles,
    Filter,
    GitBranch,
} from 'lucide';

/**
 * Convert a Lucide icon node definition to an SVG HTML string.
 *
 * @param {Array} iconNode - Lucide icon definition (array of [tag, attrs] tuples)
 * @param {number} size - Rendered size in pixels (default 14)
 * @returns {string} SVG HTML string
 */
function renderIcon(iconNode, size = 18) {
    const children = iconNode
        .map(([tag, attrs]) => {
            const attrStr = Object.entries(attrs)
                .map(([k, v]) => `${k}="${v}"`)
                .join(' ');
            return `<${tag} ${attrStr}/>`;
        })
        .join('');

    return `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${children}</svg>`;
}

// ── Node type icons ─────────────────────────────────────────────────
export const ICON_TRIGGER      = renderIcon(Zap);
export const ICON_ACTION       = renderIcon(Play);
export const ICON_CONDITION    = renderIcon(Activity);
export const ICON_DELAY        = renderIcon(Clock);
export const ICON_LOOP         = renderIcon(Repeat);
export const ICON_STOP         = renderIcon(Square);

// ── Action category icons ───────────────────────────────────────────
export const ICON_RECORD       = renderIcon(FileInput);
export const ICON_MAIL         = renderIcon(Mail);
export const ICON_WEBHOOK      = renderIcon(Webhook);
export const ICON_GLOBE        = renderIcon(Globe);

// ── Per-action icons ────────────────────────────────────────────────
export const ICON_FILE_PLUS    = renderIcon(FilePlus);
export const ICON_FILE_PEN     = renderIcon(FilePen);
export const ICON_FILE_SEARCH  = renderIcon(FileSearch);
export const ICON_FILE_X       = renderIcon(FileX);
export const ICON_MESSAGE      = renderIcon(MessageSquare);
export const ICON_FILE_TEXT    = renderIcon(FileText);
export const ICON_TAG          = renderIcon(Tag);
export const ICON_CALCULATOR   = renderIcon(Calculator);
export const ICON_BAR_CHART    = renderIcon(BarChart3);
export const ICON_CLOCK_UP     = renderIcon(ClockArrowUp);
export const ICON_DICE         = renderIcon(Dices);
export const ICON_MEGAPHONE    = renderIcon(Megaphone);
export const ICON_PARTY        = renderIcon(PartyPopper);
export const ICON_BRACES       = renderIcon(Braces);
export const ICON_SPARKLES     = renderIcon(Sparkles);
export const ICON_FILTER       = renderIcon(Filter);
export const ICON_GIT_BRANCH   = renderIcon(GitBranch);

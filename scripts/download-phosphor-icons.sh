#!/bin/bash

# Download Phosphor icons from the official repository
# This script fetches only the icons we need for the Relaticle application

set -e

BASE_URL="https://raw.githubusercontent.com/phosphor-icons/core/main/assets"

# Target directories
REGULAR_DIR="resources/svg/phosphor/regular"
BOLD_DIR="resources/svg/phosphor/bold"
DUOTONE_DIR="resources/svg/phosphor/duotone"
FILL_DIR="resources/svg/phosphor/fill"

# Create directories
mkdir -p "$REGULAR_DIR" "$BOLD_DIR" "$DUOTONE_DIR" "$FILL_DIR"

# List of icons needed (Phosphor naming convention)
ICONS=(
    "arrow-bend-up-left"
    "arrow-circle-right"
    "arrow-down"
    "arrow-fat-lines-down"
    "arrow-fat-lines-up"
    "arrow-left"
    "arrow-right"
    "arrow-square-out"
    "arrow-u-up-left"
    "arrows-clockwise"
    "arrows-down-up"
    "bank"
    "book-open"
    "buildings"
    "calendar"
    "calendar-blank"
    "caret-down"
    "caret-right"
    "chart-line-up"
    "chart-line-down"
    "check"
    "check-circle"
    "checks"
    "clipboard"
    "clipboard-text"
    "clock"
    "code"
    "desktop"
    "currency-circle-dollar"
    "currency-dollar"
    "device-mobile"
    "dots-three"
    "envelope"
    "eye"
    "file"
    "file-text"
    "files"
    "flag"
    "funnel"
    "gear"
    "gear-six"
    "globe"
    "grid-four"
    "hand-pointing"
    "hash"
    "house"
    "key"
    "link"
    "list"
    "list-bullets"
    "list-dashes"
    "magnifying-glass"
    "moon"
    "note"
    "note-pencil"
    "pencil"
    "pencil-simple"
    "plus"
    "prohibit"
    "queue"
    "rocket-launch"
    "rows"
    "seal-check"
    "shield-check"
    "shopping-cart"
    "sign-out"
    "sparkle"
    "spinner"
    "squares-four"
    "sun"
    "table"
    "trash"
    "translate"
    "trophy"
    "upload"
    "user"
    "user-circle"
    "users"
    "users-three"
    "columns"
    "warning"
    "x"
    "x-circle"
)

echo "Downloading Phosphor icons..."

download_icon() {
    local icon=$1
    local weight=$2
    local dir=$3
    local suffix=$4

    local url="${BASE_URL}/${weight}/${icon}${suffix}.svg"
    local output="${dir}/${icon}.svg"

    if curl -sf "$url" -o "$output" 2>/dev/null; then
        echo "  ✓ ${weight}/${icon}"
        return 0
    else
        echo "  ✗ ${weight}/${icon} (not found)"
        return 1
    fi
}

# Download each icon in all weights
for icon in "${ICONS[@]}"; do
    download_icon "$icon" "regular" "$REGULAR_DIR" ""
    download_icon "$icon" "bold" "$BOLD_DIR" "-bold"
    download_icon "$icon" "duotone" "$DUOTONE_DIR" "-duotone"
    download_icon "$icon" "fill" "$FILL_DIR" "-fill"
done

echo ""
echo "Download complete!"
echo "Regular: $(ls -1 "$REGULAR_DIR" 2>/dev/null | wc -l | tr -d ' ') icons"
echo "Bold: $(ls -1 "$BOLD_DIR" 2>/dev/null | wc -l | tr -d ' ') icons"
echo "Duotone: $(ls -1 "$DUOTONE_DIR" 2>/dev/null | wc -l | tr -d ' ') icons"
echo "Fill: $(ls -1 "$FILL_DIR" 2>/dev/null | wc -l | tr -d ' ') icons"

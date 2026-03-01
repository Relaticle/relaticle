# Project Instructions

## Authentication

Use the Developer Login button on the login page.

## Tech Stack

- Laravel + Filament v3 (admin panel)
- Livewire v3 (SPA navigation via wire:navigate)
- Alpine.js (bundled with Livewire)
- Multi-tenant (team-based, ULID primary keys)
- Workflow package: `packages/workflow/`

## Workflow Package Notes

- Frontend assets must use `@assets`/`@endassets` Blade directive (NOT `@push('scripts')`) to work with Livewire SPA navigation
- JS built as IIFE via Vite to `public/vendor/workflow/`
- Alpine components must handle both pre- and post-Alpine initialization (check `window.Alpine` existence)
- Build: `cd packages/workflow && npm run build`
- Tests: `packages/workflow/vendor/bin/pest packages/workflow/tests/Feature/`

## Workflow UI/UX Reference (Attio)

- Reference docs and screenshots: `attio-workflow/` (Attio's workflow product as UX benchmark)
- Design patterns analysis: `attio-workflow/DESIGN-PATTERNS.md`
- Key pages: getting-started, create-a-workflow, block-library, manage-workflows, troubleshooting
- All UI screenshots in `attio-workflow/images/` — use for visual comparison when polishing workflow UX

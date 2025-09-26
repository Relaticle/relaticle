@php
    use Filament\Support\Enums\Width;

    $livewire ??= null;

    $hasTopbar = filament()->hasTopbar();
    $isSidebarCollapsibleOnDesktop = filament()->isSidebarCollapsibleOnDesktop();
    $isSidebarFullyCollapsibleOnDesktop = filament()->isSidebarFullyCollapsibleOnDesktop();
    $hasTopNavigation = filament()->hasTopNavigation();
    $hasNavigation = filament()->hasNavigation();
    $renderHookScopes = $livewire?->getRenderHookScopes();
    $maxContentWidth ??= (filament()->getMaxContentWidth() ?? Width::SevenExtraLarge);

    if (is_string($maxContentWidth)) {
        $maxContentWidth = Width::tryFrom($maxContentWidth) ?? $maxContentWidth;
    }
@endphp

<x-filament-panels::layout.base
    :livewire="$livewire"
    @class([
        'fi-body-has-navigation' => $hasNavigation,
        'fi-body-has-sidebar-collapsible-on-desktop' => $isSidebarCollapsibleOnDesktop,
        'fi-body-has-sidebar-fully-collapsible-on-desktop' => $isSidebarFullyCollapsibleOnDesktop,
        'fi-body-has-topbar' => $hasTopbar,
        'fi-body-has-top-navigation' => $hasTopNavigation,
    ])
>
    {{-- Custom layout structure with full height sidebar and topbar --}}
    <div style="display: flex; height: 100vh; overflow: hidden;">
        {{-- Sidebar overlay for mobile --}}
        @if ($hasNavigation)
            <div
                x-cloak
                x-data="{}"
                x-on:click="$store.sidebar.close()"
                x-show="$store.sidebar.isOpen"
                x-transition.opacity.300ms
                class="fi-sidebar-close-overlay"
                style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 29;"
                @media (min-width: 1024px) {
                    style="display: none;"
                }
            ></div>

            {{-- Sidebar - Keep all original Alpine.js functionality --}}
            @livewire(filament()->getSidebarLivewireComponent())
        @endif

        {{-- Main content wrapper --}}
        <div style="flex: 1; display: flex; flex-direction: column; overflow: hidden;">
            {{-- Topbar --}}
            @if ($hasTopbar)
                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::TOPBAR_BEFORE, scopes: $renderHookScopes) }}

                @livewire(filament()->getTopbarLivewireComponent())

                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::TOPBAR_AFTER, scopes: $renderHookScopes) }}
            @endif

            {{-- Page content area --}}
            <div class="fi-layout" style="flex: 1; overflow-y: auto;">
                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::LAYOUT_START, scopes: $renderHookScopes) }}

                <div
                    @if ($isSidebarCollapsibleOnDesktop)
                        x-data="{}"
                        x-bind:class="{
                            'fi-main-ctn-sidebar-open': $store.sidebar.isOpen,
                        }"
                        x-bind:style="'display: flex; opacity:1;'"
                        {{-- Mimics `x-cloak`, as using `x-cloak` causes visual issues with chart widgets --}}
                    @elseif ($isSidebarFullyCollapsibleOnDesktop)
                        x-data="{}"
                        x-bind:class="{
                            'fi-main-ctn-sidebar-open': $store.sidebar.isOpen,
                        }"
                        x-bind:style="'display: flex; opacity:1;'"
                        {{-- Mimics `x-cloak`, as using `x-cloak` causes visual issues with chart widgets --}}
                    @elseif (! ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop || $hasTopNavigation || (! $hasNavigation)))
                        x-data="{}"
                        x-bind:style="'display: flex; opacity:1;'" {{-- Mimics `x-cloak`, as using `x-cloak` causes visual issues with chart widgets --}}
                    @endif
                    class="fi-main-ctn"
                    style="min-height: 100%;"
                >
                    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::CONTENT_BEFORE, scopes: $renderHookScopes) }}

                    <main
                        @class([
                            'fi-main',
                            ($maxContentWidth instanceof Width) ? "fi-width-{$maxContentWidth->value}" : $maxContentWidth,
                        ])
                    >
                        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::CONTENT_START, scopes: $renderHookScopes) }}

                        {{ $slot }}

                        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::CONTENT_END, scopes: $renderHookScopes) }}
                    </main>

                    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::CONTENT_AFTER, scopes: $renderHookScopes) }}

                    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::FOOTER, scopes: $renderHookScopes) }}
                </div>

                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::LAYOUT_END, scopes: $renderHookScopes) }}
            </div>
        </div>
    </div>
</x-filament-panels::layout.base>
<!-- Minimalist Header -->
@push('scripts')
<script>
    // On page load or when changing themes, best to add inline in `head` to avoid FOUC
    if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
</script>
@endpush

<header class="bg-white dark:bg-black py-4 fixed w-full top-0 z-50 transition-all duration-300 border-b border-gray-100 dark:border-gray-900 backdrop-blur-sm bg-white/95 dark:bg-black/95">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Three-column layout: Logo | Nav (centered) | Actions -->
        <div class="flex items-center justify-between">
            <!-- Logo (Left Column) -->
            <div class="flex-shrink-0 w-1/4">
                <a href="{{ url('/') }}" class="flex items-center space-x-2.5 group" aria-label="Relaticle Home">
                    <div class="relative overflow-hidden">
                        <img class="h-8 w-auto transition-transform duration-300 group-hover:scale-105" src="{{ asset('relaticle-logo.svg') }}" alt="Relaticle Logo">
                    </div>
                    <span class="font-bold text-lg text-black dark:text-white hidden sm:block transition-opacity duration-300 group-hover:opacity-80">Relaticle</span>
                </a>
            </div>

            <!-- Desktop Navigation (Center Column) -->
            <div class="hidden md:flex flex-grow items-center justify-center">
                <nav class="flex items-center space-x-8">
                    <a href="{{ url('/#features') }}"
                        class="text-gray-700 dark:text-white hover:text-primary dark:hover:text-primary-400 text-sm font-medium transition-all duration-200 relative after:absolute after:bottom-0 after:left-0 after:right-0 after:h-0.5 after:w-0 after:bg-primary dark:after:bg-primary after:transition-all hover:after:w-full"
                        aria-label="Product features">Features</a>
                    <a href="{{ route('documentation.index') }}"
                        class="text-gray-700 dark:text-white hover:text-primary dark:hover:text-primary-400 text-sm font-medium transition-all duration-200 relative after:absolute after:bottom-0 after:left-0 after:right-0 after:h-0.5 after:w-0 after:bg-primary dark:after:bg-primary after:transition-all hover:after:w-full"
                        aria-label="Documentation">Documentation</a>
                    <a href="https://github.com/Relaticle/relaticle" target="_blank" rel="noopener"
                        class="text-gray-700 dark:text-white hover:text-primary dark:hover:text-primary-400 text-sm font-medium transition-all duration-200 flex items-center gap-1.5 relative after:absolute after:bottom-0 after:left-0 after:right-0 after:h-0.5 after:w-0 after:bg-primary dark:after:bg-primary after:transition-all hover:after:w-full"
                        aria-label="GitHub Repository">
                        <x-icon-github class="w-4 h-4" />
                        <span>GitHub</span>
                        <x-heroicon-o-arrow-up-right class="h-3 w-3 text-gray-400" />
                    </a>
                </nav>
            </div>

            <!-- Right Section: Auth and Settings (Right Column) -->
            <div class="flex items-center justify-end space-x-5 w-1/4">
                <!-- Dark Mode Toggle -->
                <button id="theme-toggle"
                    class="p-2 text-gray-500 dark:text-gray-400 hover:text-primary dark:hover:text-primary-400 focus:outline-none focus:ring-2 focus:ring-primary/30 rounded-full transition-transform duration-300 active:scale-90"
                    aria-label="Toggle dark mode">
                    <x-heroicon-o-sun class="h-5 w-5 hidden dark:block" />
                    <x-heroicon-o-moon class="h-5 w-5 block dark:hidden" />
                </button>

                <!-- Auth Links -->
                <div class="hidden lg:flex items-center space-x-6">
                    <a href="{{ route('login') }}"
                        class="text-gray-700 dark:text-white hover:text-primary dark:hover:text-primary-400 text-sm font-medium transition-all duration-200 {{ Route::is('login') ? 'text-primary dark:text-primary-400' : '' }}"
                        aria-label="Sign in to your account">Sign In</a>

                    <a href="{{ route('register') }}"
                        class="group bg-primary hover:bg-primary-600 text-white px-5 py-2.5 text-sm rounded-md transition-all duration-300 font-medium relative overflow-hidden shadow-sm hover:shadow hover:scale-[1.02] active:scale-[0.98]"
                        aria-label="Create a new account">
                        <span class="relative z-10">Get Started</span>
                        <span class="absolute inset-0 bg-gradient-to-r from-primary-600 to-primary-700 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></span>
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden">
                    <button id="mobile-menu-button"
                        class="p-2 text-gray-500 dark:text-gray-400 hover:text-primary dark:hover:text-primary-400 focus:outline-none focus:ring-2 focus:ring-primary/30 rounded-full transition-all active:scale-95"
                        aria-label="Toggle mobile menu"
                        aria-expanded="false"
                        aria-controls="mobile-menu">
                        <x-heroicon-o-bars-3 class="h-6 w-6" id="menu-icon" />
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Menu with Better Styling -->
    <div id="mobile-menu-backdrop" class="hidden fixed inset-0 bg-black/20 dark:bg-black/40 backdrop-blur-sm z-40 transition-opacity duration-300 opacity-0 md:hidden"></div>
    <div id="mobile-menu"
        class="md:hidden hidden h-screen border-l border-gray-200 dark:border-gray-800 fixed inset-y-0 right-0 w-[280px] max-w-[90vw] bg-white dark:bg-gray-900 shadow-xl z-50 transform translate-x-full transition-transform duration-300 ease-in-out overflow-y-auto">
        <div class="p-6 flex flex-col h-full">
            <!-- Header with close button -->
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center space-x-2">
                    <img class="h-7 w-auto" src="{{ asset('relaticle-logo.svg') }}" alt="Relaticle Logo">
                </div>
                <button id="mobile-menu-close"
                    class="p-1.5 text-gray-500 dark:text-gray-400 hover:text-primary dark:hover:text-primary-400 focus:outline-none focus:ring-2 focus:ring-primary/30 rounded-full transition-all active:scale-90"
                    aria-label="Close menu">
                    <x-heroicon-o-x-mark class="h-5 w-5" />
                </button>
            </div>

            <!-- Navigation Section Label -->
            <div class="mb-2">
                <span class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Navigation</span>
            </div>

            <!-- Navigation Links with staggered animation classes -->
            <nav class="flex-grow space-y-1 mb-8">
                <a href="#features"
                    class="menu-item opacity-0 transform translate-x-4 flex items-center px-3 py-3 text-gray-700 dark:text-gray-200 hover:text-primary dark:hover:text-primary-400 text-base transition-all duration-200 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800/70 {{ request()->is('/') || request()->is('/#features') ? 'bg-primary/5 dark:bg-primary/10 text-primary dark:text-primary-400' : '' }}"
                    style="transition-delay: 100ms;">
                    <x-heroicon-o-squares-2x2 class="mr-3 h-5 w-5 {{ request()->is('/') || request()->is('/#features') ? 'text-primary dark:text-primary-400' : '' }}" />
                    <span class="font-medium">Features</span>
                </a>

                <a href="{{ route('documentation.index') }}"
                    class="menu-item opacity-0 transform translate-x-4 flex items-center px-3 py-3 text-gray-700 dark:text-gray-200 hover:text-primary dark:hover:text-primary-400 text-base transition-all duration-200 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800/70 {{ Route::is('documentation.*') ? 'bg-primary/5 dark:bg-primary/10 text-primary dark:text-primary-400' : '' }}"
                    style="transition-delay: 150ms;">
                    <x-heroicon-o-document-text class="mr-3 h-5 w-5 {{ Route::is('documentation.*') ? 'text-primary dark:text-primary-400' : '' }}" />
                    <span class="font-medium">Documentation</span>
                </a>

                <a href="https://github.com/Relaticle/relaticle" target="_blank" rel="noopener"
                    class="menu-item opacity-0 transform translate-x-4 flex items-center px-3 py-3 text-gray-700 dark:text-gray-200 hover:text-primary dark:hover:text-primary-400 text-base transition-all duration-200 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800/70"
                    style="transition-delay: 200ms;">
                    <x-icon-github />
                    <span class="font-medium">GitHub</span>
                    <x-heroicon-o-arrow-top-right-on-square class="ml-auto h-4 w-4 text-gray-400" />
                </a>
            </nav>

            <!-- Account Section Label -->
            <div class="mb-2">
                <span class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Account</span>
            </div>

            <!-- Auth Links in Mobile Menu with staggered animation classes -->
            <div class="space-y-1 mb-8">
                <a href="{{ route('login') }}"
                    class="menu-item opacity-0 transform translate-x-4 flex items-center px-3 py-3 text-gray-700 dark:text-gray-200 hover:text-primary dark:hover:text-primary-400 text-base transition-all duration-200 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800/70 {{ Route::is('login') ? 'bg-primary/5 dark:bg-primary/10 text-primary dark:text-primary-400' : '' }}"
                    style="transition-delay: 250ms;">
                    <x-heroicon-o-arrow-right-on-rectangle class="mr-3 h-5 w-5 {{ Route::is('login') ? 'text-primary dark:text-primary-400' : '' }}" />
                    <span class="font-medium">Sign In</span>
                    @if(Route::is('login'))
                        <span class="ml-auto bg-primary/10 dark:bg-primary/20 text-primary dark:text-primary-400 text-xs font-medium px-2 py-0.5 rounded-full">Active</span>
                    @endif
                </a>

                <a href="{{ route('register') }}"
                    class="menu-item opacity-0 transform translate-x-4 mt-2 flex items-center justify-center text-center py-3 px-3 bg-primary hover:bg-primary-600 text-white text-base rounded-lg font-medium transition-all duration-200 shadow-sm active:scale-[0.98] {{ Route::is('register') ? 'bg-primary-600' : '' }}"
                    style="transition-delay: 300ms;">
                    <x-heroicon-o-user-plus class="mr-3 h-5 w-5" />
                    Get Started
                </a>
            </div>

            <!-- Theme Toggle in Mobile Menu -->
            <div class="menu-item opacity-0 transform translate-x-4 mt-auto pt-6 border-t border-gray-100 dark:border-gray-800"
                style="transition-delay: 350ms;">
                <button
                    class="mobile-theme-toggle w-full flex items-center justify-between px-3 py-3 text-gray-700 dark:text-gray-200 hover:text-primary dark:hover:text-primary-400 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800/70"
                    aria-label="Toggle dark mode">
                    <div class="flex items-center">
                        <x-heroicon-o-sun class="mr-3 h-5 w-5 hidden dark:inline-block" />
                        <x-heroicon-o-moon class="mr-3 h-5 w-5 inline-block dark:hidden" />
                    </div>
                    <div class="relative">
                        <div class="w-10 h-5 bg-gray-200 dark:bg-gray-700 rounded-full shadow-inner transition-colors duration-200"></div>
                        <div class="dot absolute left-0 top-0 bg-white dark:bg-primary w-5 h-5 rounded-full shadow transform dark:translate-x-5 transition-transform duration-200"></div>
                    </div>
                </button>
            </div>

            <!-- Version Info -->
            <div class="menu-item opacity-0 text-center mt-8 text-xs text-gray-400"
                style="transition-delay: 400ms;">
                <p>Relaticle v1.0.0</p>
            </div>
        </div>
    </div>
</header>

<!-- Add a spacer to prevent content from hiding behind fixed header -->
<div class="h-[64px]"></div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Dark mode toggle functionality - define at the top level
        const themeToggleButton = document.getElementById('theme-toggle');
        const htmlElement = document.documentElement;
        const mobileThemeToggle = document.querySelector('.mobile-theme-toggle');

        // Function to toggle dark mode
        function toggleDarkMode() {
            if (htmlElement.classList.contains('dark')) {
                htmlElement.classList.remove('dark');
                localStorage.theme = 'light';
            } else {
                htmlElement.classList.add('dark');
                localStorage.theme = 'dark';
            }
        }

        // Initialize theme on page load
        document.documentElement.classList.toggle(
            "dark",
            localStorage.theme === "dark" ||
            (!("theme" in localStorage) && window.matchMedia("(prefers-color-scheme: dark)").matches)
        );

        // Add event listeners to theme toggle buttons
        if (themeToggleButton) {
            themeToggleButton.addEventListener('click', toggleDarkMode);
        }

        // Mobile menu functionality - enhanced
        const menuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        const mobileMenuBackdrop = document.getElementById('mobile-menu-backdrop');
        const mobileMenuClose = document.getElementById('mobile-menu-close');
        const bodyElement = document.body;
        const menuItems = document.querySelectorAll('.menu-item');

        function openMobileMenu() {
            // Show menu with animation
            mobileMenu.classList.remove('hidden');
            mobileMenuBackdrop.classList.remove('hidden');

            // Prevent body scrolling when menu is open
            bodyElement.classList.add('overflow-hidden');

            // Trigger transitions after elements are visible
            setTimeout(() => {
                mobileMenu.classList.remove('translate-x-full');
                mobileMenuBackdrop.classList.remove('opacity-0');

                // Animate in menu items with staggered delay
                menuItems.forEach(item => {
                    setTimeout(() => {
                        item.classList.remove('opacity-0', 'translate-x-4');
                    }, 50); // Small additional delay for smooth animation
                });
            }, 10);

            menuButton.setAttribute('aria-expanded', 'true');
        }

        function closeMobileMenu() {
            // Hide menu with animation
            mobileMenu.classList.add('translate-x-full');
            mobileMenuBackdrop.classList.add('opacity-0');

            // Reset menu item animations
            menuItems.forEach(item => {
                item.classList.add('opacity-0', 'translate-x-4');
            });

            // Re-enable body scrolling
            bodyElement.classList.remove('overflow-hidden');

            // Wait for transition to complete before hiding elements
            setTimeout(() => {
                mobileMenu.classList.add('hidden');
                mobileMenuBackdrop.classList.add('hidden');
            }, 300);

            menuButton.setAttribute('aria-expanded', 'false');
        }

        if (menuButton && mobileMenu) {
            menuButton.addEventListener('click', openMobileMenu);

            // Close menu when clicking close button
            if (mobileMenuClose) {
                mobileMenuClose.addEventListener('click', closeMobileMenu);
            }

            // Close menu when clicking backdrop
            if (mobileMenuBackdrop) {
                mobileMenuBackdrop.addEventListener('click', closeMobileMenu);
            }

            // Close menu when pressing Escape key
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && menuButton.getAttribute('aria-expanded') === 'true') {
                    closeMobileMenu();
                }
            });

            // Mobile theme toggle functionality
            if (mobileThemeToggle) {
                mobileThemeToggle.addEventListener('click', toggleDarkMode);
            }
        }

        // Dynamic header scrolling effect
        const header = document.querySelector('header');
        let lastScrollTop = 0;

        window.addEventListener('scroll', function() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

            if (scrollTop > 20) {
                header.classList.add('py-3', 'shadow-sm');
                header.classList.remove('py-4');
            } else {
                header.classList.add('py-4');
                header.classList.remove('py-3', 'shadow-sm');
            }

            lastScrollTop = scrollTop;
        });

        // Check for user preference for reduced motion
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (prefersReducedMotion) {
            // Apply styles for users who prefer reduced motion
            document.documentElement.classList.add('reduce-motion');
        }
    });
</script>

<style>
    /* Adding styles for users who prefer reduced motion */
    .reduce-motion * {
        transition-duration: 0.05s !important;
        animation-duration: 0.05s !important;
    }
</style>

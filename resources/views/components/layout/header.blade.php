<header id="marketing-header" class="fixed top-0 z-50 w-full py-4 transition-all duration-300">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div id="marketing-header-shell"
             class="relative overflow-hidden rounded-2xl border border-gray-200/70 bg-white/75 shadow-sm backdrop-blur-xl transition-all duration-300 dark:border-gray-800/80 dark:bg-black/70">
            <div class="pointer-events-none absolute inset-x-8 top-0 h-px bg-gradient-to-r from-transparent via-primary/40 to-transparent"></div>

            <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-6">
                <div class="flex min-w-0 flex-1 items-center gap-3">
                    <a href="{{ url('/') }}" class="inline-flex w-fit" aria-label="Relaticle Home">
                        <x-brand.logo-lockup size="md" class="text-black dark:text-white"/>
                    </a>
                    <span
                        class="hidden rounded-full border border-primary/20 bg-primary/5 px-2.5 py-1 text-xs font-medium text-primary lg:inline-flex">
                        2026-ready
                    </span>
                </div>

                <div class="hidden flex-1 items-center justify-center md:flex">
                    <nav class="inline-flex items-center gap-1 rounded-full border border-gray-200/80 bg-white/70 p-1 text-sm dark:border-gray-700 dark:bg-gray-900/80">
                        <a href="{{ url('/#features') }}"
                           class="rounded-full px-4 py-2 font-medium text-gray-600 transition-all duration-200 hover:bg-primary/10 hover:text-primary dark:text-gray-300 dark:hover:bg-primary/20 dark:hover:text-primary-300"
                           aria-label="Product features">
                            Features
                        </a>
                        <a href="{{ route('documentation.index') }}"
                           class="rounded-full px-4 py-2 font-medium text-gray-600 transition-all duration-200 hover:bg-primary/10 hover:text-primary dark:text-gray-300 dark:hover:bg-primary/20 dark:hover:text-primary-300"
                           aria-label="Documentation">
                            Documentation
                        </a>
                        <a href="https://github.com/Relaticle/relaticle"
                           target="_blank"
                           rel="noopener"
                           class="inline-flex items-center gap-1.5 rounded-full px-4 py-2 font-medium text-gray-600 transition-all duration-200 hover:bg-primary/10 hover:text-primary dark:text-gray-300 dark:hover:bg-primary/20 dark:hover:text-primary-300"
                           aria-label="GitHub Repository">
                            <x-icon-github class="h-4 w-4"/>
                            @if(isset($githubStars) && $githubStars > 0)
                                <span>{{ $formattedGithubStars }}</span>
                            @endif
                        </a>
                        <a href="{{ route('discord') }}"
                           target="_blank"
                           class="inline-flex items-center gap-1.5 rounded-full px-4 py-2 font-medium text-gray-600 transition-all duration-200 hover:bg-primary/10 hover:text-primary dark:text-gray-300 dark:hover:bg-primary/20 dark:hover:text-primary-300"
                           aria-label="Join Discord Community">
                            <x-icon-discord class="h-4 w-4"/>
                            Discord
                        </a>
                    </nav>
                </div>

                <div class="flex flex-1 items-center justify-end gap-3">
                    <div class="hidden items-center gap-3 md:flex">
                        <x-theme-switcher/>

                        <a href="{{ route('login') }}"
                           class="rounded-full px-4 py-2 text-sm font-medium text-gray-600 transition-all duration-200 hover:bg-gray-100 hover:text-black dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white {{ Route::is('login') ? 'bg-gray-100 text-black dark:bg-gray-800 dark:text-white' : '' }}"
                           aria-label="Sign in to your account">
                            Sign in
                        </a>

                        <a href="{{ route('register') }}"
                           class="group relative inline-flex items-center justify-center overflow-hidden rounded-full border border-primary/70 bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-md transition-all duration-300 hover:-translate-y-0.5 hover:bg-primary-600 hover:shadow-lg"
                           aria-label="Create a new account">
                            <span class="relative z-10">Start free</span>
                            <span class="absolute inset-0 bg-gradient-to-r from-primary-600 via-primary-500 to-primary-700 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></span>
                        </a>
                    </div>

                    <div class="md:hidden">
                        <button id="mobile-menu-button"
                                class="rounded-full border border-gray-200/80 bg-white/80 p-2 text-gray-600 transition-all hover:border-primary/30 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/30 active:scale-95 dark:border-gray-700/80 dark:bg-gray-900/85 dark:text-gray-300 dark:hover:text-primary-300"
                                aria-label="Toggle mobile menu"
                                aria-expanded="false">
                            <x-heroicon-o-bars-3 class="h-6 w-6"/>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-layout.mobile-menu/>
</header>

<div class="h-[92px] sm:h-[100px]"></div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        const mobileMenuBackdrop = document.getElementById('mobile-menu-backdrop');
        const mobileMenuClose = document.getElementById('mobile-menu-close');
        const mobileMenuLinks = document.querySelectorAll('.mobile-menu-link');

        function openMobileMenu() {
            if (!mobileMenu || !mobileMenuBackdrop || !mobileMenuButton) {
                return;
            }

            mobileMenu.classList.remove('hidden');
            mobileMenuBackdrop.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');

            setTimeout(() => {
                mobileMenu.classList.remove('translate-x-full');
                mobileMenuBackdrop.classList.remove('opacity-0');
            }, 10);

            mobileMenuButton.setAttribute('aria-expanded', 'true');
        }

        function closeMobileMenu() {
            if (!mobileMenu || !mobileMenuBackdrop || !mobileMenuButton) {
                return;
            }

            mobileMenu.classList.add('translate-x-full');
            mobileMenuBackdrop.classList.add('opacity-0');
            document.body.classList.remove('overflow-hidden');

            setTimeout(() => {
                mobileMenu.classList.add('hidden');
                mobileMenuBackdrop.classList.add('hidden');
            }, 300);

            mobileMenuButton.setAttribute('aria-expanded', 'false');
        }

        if (mobileMenuButton) {
            mobileMenuButton.addEventListener('click', openMobileMenu);
        }

        if (mobileMenuClose) {
            mobileMenuClose.addEventListener('click', closeMobileMenu);
        }

        if (mobileMenuBackdrop) {
            mobileMenuBackdrop.addEventListener('click', closeMobileMenu);
        }

        mobileMenuLinks.forEach(link => {
            link.addEventListener('click', closeMobileMenu);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && mobileMenuButton && mobileMenuButton.getAttribute('aria-expanded') === 'true') {
                closeMobileMenu();
            }
        });

        const header = document.getElementById('marketing-header');
        const headerShell = document.getElementById('marketing-header-shell');
        const updateHeaderOnScroll = function () {
            if (!header || !headerShell) {
                return;
            }

            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            if (scrollTop > 20) {
                header.classList.add('py-2');
                header.classList.remove('py-4');
                headerShell.classList.add('shadow-xl', 'bg-white/90', 'dark:bg-black/85');
                headerShell.classList.remove('shadow-sm', 'bg-white/75', 'dark:bg-black/70');
            } else {
                header.classList.add('py-4');
                header.classList.remove('py-2');
                headerShell.classList.add('shadow-sm', 'bg-white/75', 'dark:bg-black/70');
                headerShell.classList.remove('shadow-xl', 'bg-white/90', 'dark:bg-black/85');
            }
        };

        updateHeaderOnScroll();
        window.addEventListener('scroll', updateHeaderOnScroll, {passive: true});

        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (prefersReducedMotion) {
            document.documentElement.classList.add('reduce-motion');
        }
    });
</script>

<style>
    .reduce-motion * {
        transition-duration: 0.05s !important;
        animation-duration: 0.05s !important;
    }
</style>

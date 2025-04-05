<!-- Minimalist Header -->
<header class="bg-white dark:bg-black py-5 fixed w-full top-0 z-50 transition-all duration-300 border-b border-gray-100 dark:border-gray-900 backdrop-blur-sm bg-white/95 dark:bg-black/95">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between">
            <!-- Logo -->
            <div class="flex-shrink-0">
                <a href="{{ url('/') }}" class="flex items-center space-x-2.5 group" aria-label="Relaticle Home">
                    <div class="relative overflow-hidden p-0.5">
                        <img class="h-8 w-auto transition-transform duration-300 group-hover:scale-105" src="{{ asset('relaticle-logo.svg') }}" alt="Relaticle Logo">
                    </div>
                    <span class="font-bold text-lg text-black dark:text-white hidden sm:block transition-opacity duration-300 group-hover:opacity-80">Relaticle</span>
                </a>
            </div>

            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center space-x-10">
                <a href="{{ url('/#features') }}"
                    class="text-gray-600 dark:text-gray-300 hover:text-black dark:hover:text-white text-sm font-medium transition-all duration-200 relative after:absolute after:bottom-0 after:left-0 after:right-0 after:h-0.5 after:w-0 after:bg-black dark:after:bg-white after:transition-all hover:after:w-full"
                    aria-label="Product features">Features</a>
                <a href="{{ route('documentation.index') }}"
                    class="text-gray-600 dark:text-gray-300 hover:text-black dark:hover:text-white text-sm font-medium transition-all duration-200 relative after:absolute after:bottom-0 after:left-0 after:right-0 after:h-0.5 after:w-0 after:bg-black dark:after:bg-white after:transition-all hover:after:w-full"
                    aria-label="Documentation">Docs</a>
                <a href="https://github.com/Relaticle" target="_blank" rel="noopener"
                    class="text-gray-600 dark:text-gray-300 hover:text-black dark:hover:text-white text-sm font-medium transition-all duration-200 flex items-center gap-1.5 relative after:absolute after:bottom-0 after:left-0 after:right-0 after:h-0.5 after:w-0 after:bg-black dark:after:bg-white after:transition-all hover:after:w-full"
                    aria-label="GitHub Repository">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.237 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                    </svg>
                    <span>GitHub</span>
                </a>
            </div>

            <!-- Right Section: Auth and Settings -->
            <div class="flex items-center space-x-5">
                <!-- Dark Mode Toggle -->
                <button id="theme-toggle" 
                    class="p-2 text-gray-500 dark:text-gray-400 hover:text-black dark:hover:text-white focus:outline-none transition-transform active:scale-90"
                    aria-label="Toggle dark mode">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>

                <!-- Auth Links -->
                <div class="hidden lg:flex items-center space-x-5">
                    <a href="{{ route('login') }}"
                        class="text-gray-600 dark:text-gray-300 hover:text-black dark:hover:text-white text-sm font-medium transition-all duration-200"
                        aria-label="Sign in to your account">Sign In</a>
                    
                    <a href="{{ route('register') }}"
                        class="group bg-black dark:bg-white text-white dark:text-black px-5 py-2.5 text-sm rounded-md hover:bg-gray-900 dark:hover:bg-gray-50 transition-all duration-300 font-medium relative overflow-hidden"
                        aria-label="Create a new account">
                        <span class="relative z-10">Get Started</span>
                        <span class="absolute inset-0 bg-gradient-to-r from-gray-800 to-black dark:from-gray-200 dark:to-white opacity-0 group-hover:opacity-100 transition-opacity duration-300"></span>
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden">
                    <button id="mobile-menu-button"
                        class="text-gray-500 dark:text-gray-400 hover:text-black dark:hover:text-white focus:outline-none transition-colors p-1"
                        aria-label="Toggle mobile menu"
                        aria-expanded="false"
                        aria-controls="mobile-menu">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path id="menu-icon" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div id="mobile-menu"
        class="md:hidden hidden bg-white/98 dark:bg-black/98 backdrop-blur-sm border-t border-gray-100 dark:border-gray-900 shadow-sm">
        <div class="px-4 py-5 space-y-4">
            <a href="#features"
                class="flex items-center text-gray-600 dark:text-gray-300 hover:text-black dark:hover:text-white text-sm transition-colors py-2">
                <span class="font-medium">Features</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="ml-auto h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            
            <a href="{{ route('documentation.index') }}"
                class="flex items-center text-gray-600 dark:text-gray-300 hover:text-black dark:hover:text-white text-sm transition-colors py-2">
                <span class="font-medium">Documentation</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="ml-auto h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            
            <a href="https://github.com/Relaticle" target="_blank" rel="noopener"
                class="flex items-center text-gray-600 dark:text-gray-300 hover:text-black dark:hover:text-white text-sm transition-colors py-2">
                <div class="flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.237 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                    </svg>
                    <span class="font-medium">GitHub</span>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" class="ml-auto h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            
            <div class="pt-3 border-t border-gray-100 dark:border-gray-800 space-y-4">
                <a href="{{ route('login') }}"
                    class="flex items-center text-gray-600 dark:text-gray-300 hover:text-black dark:hover:text-white text-sm transition-colors py-2">
                    <span class="font-medium">Sign In</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="ml-auto h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
                
                <a href="{{ route('register') }}"
                    class="block mt-2 bg-black dark:bg-white text-white dark:text-black px-4 py-3 text-sm rounded-md font-medium hover:bg-gray-900 dark:hover:bg-gray-50 transition-colors text-center">
                    Get Started
                </a>
            </div>
        </div>
    </div>
</header>

<!-- Add a spacer to prevent content from hiding behind fixed header -->
<div class="h-[72px]"></div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Mobile menu functionality
        const menuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        const menuIcon = document.getElementById('menu-icon');

        if (menuButton && mobileMenu && menuIcon) {
            menuButton.addEventListener('click', () => {
                const isExpanded = menuButton.getAttribute('aria-expanded') === 'true';
                menuButton.setAttribute('aria-expanded', !isExpanded);

                if (mobileMenu.classList.contains('hidden')) {
                    // Show menu
                    mobileMenu.classList.remove('hidden');
                    menuIcon.setAttribute('d', 'M6 18L18 6M6 6l12 12');
                } else {
                    // Hide menu
                    mobileMenu.classList.add('hidden');
                    menuIcon.setAttribute('d', 'M4 6h16M4 12h16M4 18h16');
                }
            });
        }

        // Dynamic header scrolling effect
        const header = document.querySelector('header');
        let lastScrollTop = 0;
        
        window.addEventListener('scroll', function() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            if (scrollTop > 50) {
                header.classList.add('py-3', 'shadow-sm');
                header.classList.remove('py-5');
            } else {
                header.classList.add('py-5');
                header.classList.remove('py-3', 'shadow-sm');
            }
            
            lastScrollTop = scrollTop;
        });

        // Dark mode toggle functionality
        const themeToggleButton = document.getElementById('theme-toggle');
        const htmlElement = document.documentElement;
        
        // Function to toggle dark mode
        function toggleDarkMode() {
            if (htmlElement.classList.contains('dark')) {
                htmlElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                htmlElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        }
        
        // Add event listeners to theme toggle buttons
        if (themeToggleButton) {
            themeToggleButton.addEventListener('click', toggleDarkMode);
        }
        
        // Initialize theme based on system preference or saved preference
        const savedTheme = localStorage.getItem('theme');
        const prefersDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if (savedTheme === 'dark' || (!savedTheme && prefersDarkMode)) {
            htmlElement.classList.add('dark');
        } else {
            htmlElement.classList.remove('dark');
        }
    });
</script>

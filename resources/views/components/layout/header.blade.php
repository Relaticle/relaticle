<!-- Enhanced Header with True Responsive Design -->
<header class="bg-white dark:bg-gray-950/95 py-4 shadow-sm fixed w-full top-0 z-50 transition-all duration-300 backdrop-blur-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Responsive flex layout with proportional spacing -->
        <div class="flex items-center justify-between">
            <!-- Logo - Responsive width -->
            <div class="flex-shrink-0 transition-transform duration-300 hover:scale-105 w-auto flex items-center">
                <a href="{{ url('/') }}" class="flex items-center space-x-2 group" aria-label="Relaticle Home">
                    <img class="h-10 w-auto sm:h-12 transform group-hover:rotate-3 transition-transform" src="{{ asset('relaticle-logo.svg') }}" alt="Relaticle Logo">
                    <span class="font-bold text-lg text-primary dark:text-white hidden sm:block">Relaticle</span>
                </a>
            </div>

            <!-- Centered navigation - adapts to available space -->
            <div class="hidden md:flex justify-center items-center flex-1 mx-4 min-w-0">
                <div class="flex-shrink-0 flex items-center space-x-1 bg-gray-100 dark:bg-gray-900/95 rounded-full px-2 py-1 backdrop-blur-sm">
                    <a href="{{ url('/#features') }}"
                       class="text-gray-700 dark:text-gray-300 hover:text-primary dark:hover:text-white px-3 py-2 sm:px-4 sm:py-2 rounded-full hover:bg-white dark:hover:bg-gray-700 transition-all duration-200 text-sm sm:text-base whitespace-nowrap"
                       aria-label="Product features">Features</a>
                    <a href="{{ route('documentation.index') }}"
                       class="text-gray-700 dark:text-gray-300 hover:text-primary dark:hover:text-white px-3 py-2 sm:px-4 sm:py-2 rounded-full hover:bg-white dark:hover:bg-gray-700 transition-all duration-200 text-sm sm:text-base whitespace-nowrap"
                       aria-label="Documentation">Docs</a>
                    <a href="https://github.com/Relaticle" target="_blank" rel="noopener"
                       class="text-gray-700 dark:text-gray-300 hover:text-primary dark:hover:text-white px-3 py-2 sm:px-4 sm:py-2 rounded-full hover:bg-white dark:hover:bg-gray-700 transition-all duration-200 flex items-center gap-1 text-sm sm:text-base whitespace-nowrap"
                       aria-label="GitHub Repository">
                        <i class="fab fa-github"></i><span class="hidden sm:inline">GitHub</span>
                    </a>
                </div>
            </div>

            <!-- Right Section: Auth Links & Dark Mode - Responsive -->
            <div class="flex items-center justify-end space-x-2 sm:space-x-3 flex-shrink-0">
                <!-- Dark Mode Toggle - Responsive sizing -->
                <button id="theme-toggle" 
                        class="hidden md:flex p-2 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                        aria-label="Toggle dark mode">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>

                <!-- Auth Links - Responsive sizing and hiding -->
                <a href="{{ route('login') }}"
                   class="hidden lg:inline-flex text-gray-700 dark:text-gray-300 hover:text-primary dark:hover:text-white px-4 py-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-gray-900 text-sm sm:text-base whitespace-nowrap"
                   aria-label="Sign in to your account">
                   <span class="relative">Sign In</span>
                </a>
                
                <!-- Get Started button - Always visible but responsive -->
                <a href="{{ route('register') }}"
                   class="hidden md:inline-flex relative group overflow-hidden bg-primary hover:bg-primary-600 dark:bg-primary-700 dark:hover:bg-primary-600 px-3 py-2 sm:px-5 sm:py-2.5 rounded-full text-white font-medium shadow-sm hover:shadow-lg transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-gray-900 text-sm sm:text-base whitespace-nowrap"
                   aria-label="Create a new account">
                    <span class="absolute -inset-full h-full w-1/2 z-5 block transform -skew-x-12 bg-white opacity-20 group-hover:animate-shine"></span>
                    <span class="flex items-center gap-1.5">
                        <span>Get Started</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </span>
                </a>

                <!-- Mobile Menu Button - Small screens only -->
                <div class="md:hidden flex items-center space-x-3">
                    <button id="mobile-theme-toggle" 
                            class="p-2 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                            aria-label="Toggle dark mode">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>
                    
                    <button id="mobile-menu-button"
                            class="text-gray-700 dark:text-gray-300 hover:text-primary dark:hover:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:ring-opacity-50 rounded-md p-1 transition-colors duration-200"
                            aria-label="Toggle mobile menu"
                            aria-expanded="false"
                            aria-controls="mobile-menu">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path id="menu-icon" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Mobile Menu with better responsiveness -->
    <div id="mobile-menu"
         class="md:hidden hidden opacity-0 transform -translate-y-4 px-4 pt-4 pb-6 space-y-4 bg-white dark:bg-gray-950 shadow-lg dark:shadow-gray-900/40 rounded-b-2xl border-t border-gray-100 dark:border-gray-800 transition-all duration-300 ease-in-out">
        <div class="space-y-4">
            <a href="#features"
               class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900/70 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-primary dark:hover:text-white transition-colors duration-200">
                <span class="font-medium">Features</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
            
            <a href="{{ route('documentation.index') }}"
               class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900/70 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-primary dark:hover:text-white transition-colors duration-200">
                <span class="font-medium">Documentation</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
            
            <a href="https://github.com/Relaticle" target="_blank" rel="noopener"
               class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900/70 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-primary dark:hover:text-white transition-colors duration-200">
                <div class="flex items-center gap-2">
                    <i class="fab fa-github"></i>
                    <span class="font-medium">GitHub</span>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
            </a>
        </div>
        
        <div class="pt-2 border-t border-gray-100 dark:border-gray-800">
            <a href="{{ route('login') }}"
               class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900/70 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-primary dark:hover:text-white transition-colors duration-200">
                <span class="font-medium">Sign In</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
            </a>
            
            <div class="mt-4">
                <a href="{{ route('register') }}"
                   class="flex items-center justify-center gap-2 w-full bg-primary hover:bg-primary-600 dark:bg-primary-700 dark:hover:bg-primary-600 text-white p-3 rounded-xl font-medium transition-all duration-200 shadow-md hover:shadow-lg">
                    <span>Get Started</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>
        </div>
    </div>
</header>

<!-- Add a spacer to prevent content from hiding behind fixed header -->
<div class="h-20"></div>

<!-- Enhanced JS for Mobile Menu and Dark Mode Functionality -->
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
                    setTimeout(() => {
                        mobileMenu.classList.remove('opacity-0', '-translate-y-4');
                    }, 10);
                    menuIcon.setAttribute('d', 'M6 18L18 6M6 6l12 12');
                } else {
                    // Hide menu
                    mobileMenu.classList.add('opacity-0', '-translate-y-4');
                    setTimeout(() => {
                        mobileMenu.classList.add('hidden');
                    }, 300);
                    menuIcon.setAttribute('d', 'M4 8h16M4 16h16');
                }
            });
        }

        // Dark mode toggle functionality
        const themeToggleButton = document.getElementById('theme-toggle');
        const mobileThemeToggleButton = document.getElementById('mobile-theme-toggle');
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
        
        if (mobileThemeToggleButton) {
            mobileThemeToggleButton.addEventListener('click', toggleDarkMode);
        }
        
        // Initialize theme based on system preference or saved preference
        const savedTheme = localStorage.getItem('theme');
        const prefersDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if (savedTheme === 'dark' || (!savedTheme && prefersDarkMode)) {
            htmlElement.classList.add('dark');
        } else {
            htmlElement.classList.remove('dark');
        }

        // Add scroll effect for header
        const header = document.querySelector('header');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 10) {
                header.classList.remove('py-4');
                header.classList.add('py-2', 'shadow');
            } else {
                header.classList.remove('py-2', 'shadow');
                header.classList.add('py-4');
            }
        });
    });
</script>

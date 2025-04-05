<!-- Enhanced Header with Modern Design and Improved UX -->
<header class="bg-white dark:bg-gray-900 py-4 shadow-sm fixed w-full top-0 z-50 transition-all duration-300 backdrop-blur-sm bg-white/90 dark:bg-gray-900/90">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center">
            <!-- Logo with hover animation -->
            <div class="flex-shrink-0 transition-transform duration-300 hover:scale-105">
                <a href="{{ url('/') }}" class="flex items-center space-x-2 group" aria-label="Relaticle Home">
                    <img class="h-12 w-auto transform group-hover:rotate-3 transition-transform" src="{{ asset('relaticle-logo.svg') }}" alt="Relaticle Logo">
                    <span class="font-bold text-lg text-primary dark:text-white hidden sm:block">Relaticle</span>
                </a>
            </div>

            <!-- Desktop Navigation Links - With modern styling -->
            <nav class="hidden md:flex flex-1 justify-center">
                <div class="flex items-center space-x-1 bg-gray-100 dark:bg-gray-800 rounded-full px-2 py-1 backdrop-blur-sm">
                    <a href="{{ url('/#features') }}"
                       class="text-gray-700 dark:text-gray-300 hover:text-primary dark:hover:text-white px-4 py-2 rounded-full hover:bg-white dark:hover:bg-gray-700 transition-all duration-200"
                       aria-label="Product features">Features</a>
                    <a href="{{ route('documentation.index') }}"
                       class="text-gray-700 dark:text-gray-300 hover:text-primary dark:hover:text-white px-4 py-2 rounded-full hover:bg-white dark:hover:bg-gray-700 transition-all duration-200"
                       aria-label="Documentation">Documentation</a>
                    <a href="https://github.com/Relaticle" target="_blank" rel="noopener"
                       class="text-gray-700 dark:text-gray-300 hover:text-primary dark:hover:text-white px-4 py-2 rounded-full hover:bg-white dark:hover:bg-gray-700 transition-all duration-200 flex items-center gap-1"
                       aria-label="GitHub Repository">
                        <i class="fab fa-github"></i><span>GitHub</span>
                    </a>
                </div>
            </nav>

            <!-- Right Section: Auth Links and Dark Mode Toggle -->
            <div class="hidden md:flex items-center space-x-3">
                <!-- Dark Mode Toggle -->
                <button id="theme-toggle"
                        class="p-2 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                        aria-label="Toggle dark mode">
                    <!-- Sun icon for dark mode -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <!-- Moon icon for light mode -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>

                <!-- Auth Links -->
                <a href="{{ route('login') }}"
                   class="text-gray-700 dark:text-gray-300 hover:text-primary dark:hover:text-white px-4 py-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                   aria-label="Sign in to your account">
                   <span class="relative">
                       Sign In
                       <span class="absolute -bottom-px left-0 w-0 h-0.5 bg-primary group-hover:w-full transition-all duration-300"></span>
                   </span>
                </a>
                <a href="{{ route('register') }}"
                   class="relative group overflow-hidden bg-primary hover:bg-primary-600 dark:bg-primary-700 dark:hover:bg-primary-600 px-5 py-2.5 rounded-full text-white font-medium shadow-sm hover:shadow-lg transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                   aria-label="Create a new account">
                    <!-- Subtle animated background effect -->
                    <span class="absolute -inset-full h-full w-1/2 z-5 block transform -skew-x-12 bg-white opacity-20 group-hover:animate-shine"></span>
                    <span class="flex items-center gap-1.5">
                        Get Started
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </span>
                </a>
            </div>

            <!-- Mobile Menu Button with improved animation and dark mode support -->
            <div class="md:hidden flex items-center space-x-3">
                <!-- Dark Mode Toggle for Mobile -->
                <button id="mobile-theme-toggle"
                        class="p-2 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                        aria-label="Toggle dark mode">
                    <!-- Sun icon for dark mode -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <!-- Moon icon for light mode -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>

                <!-- Menu Button -->
                <button id="mobile-menu-button"
                        class="text-gray-700 dark:text-gray-300 hover:text-primary dark:hover:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:ring-opacity-50 rounded-md p-1 transition-colors duration-200"
                        aria-label="Toggle mobile menu"
                        aria-expanded="false"
                        aria-controls="mobile-menu">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path id="menu-icon" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 8h16M4 16h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu with modern design and dark mode support -->
    <div id="mobile-menu"
         class="md:hidden hidden opacity-0 transform -translate-y-4 px-2 pt-2 pb-3 space-y-1 bg-white dark:bg-gray-900 shadow-lg dark:shadow-gray-800/20 rounded-b-2xl transition-all duration-300 ease-in-out">
        <!-- Tech Badge for Mobile -->
        <div class="flex items-center justify-center mb-4">
            <div class="flex px-3 py-1.5 bg-gray-100 dark:bg-gray-800 rounded-full items-center gap-2 text-xs border border-gray-200 dark:border-gray-700">
                <span class="text-gray-500 dark:text-gray-400">Built with</span>
                <img src="https://laravel.com/img/logomark.min.svg" alt="Laravel" class="h-4 w-4">
                <span class="font-medium text-gray-700 dark:text-gray-300">Laravel</span>
                <span class="text-gray-300 dark:text-gray-600">|</span>
                <img src="https://filamentphp.com/images/favicon.svg" alt="Filament" class="h-4 w-4">
                <span class="font-medium text-gray-700 dark:text-gray-300">Filament</span>
            </div>
        </div>

        <a href="#features"
           class="block text-center text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-primary dark:hover:text-white px-3 py-2 rounded-xl transition-colors duration-200">
            Features
        </a>
        <a href="{{ route('documentation.index') }}"
           class="block text-center text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-primary dark:hover:text-white px-3 py-2 rounded-xl transition-colors duration-200">
            Documentation
        </a>
        <a href="https://github.com/Relaticle" target="_blank" rel="noopener"
           class="flex items-center justify-center gap-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-primary dark:hover:text-white px-3 py-2 rounded-xl transition-colors duration-200">
            <i class="fab fa-github"></i><span>GitHub</span>
        </a>
        <a href="{{ route('login') }}"
           class="block text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-primary dark:hover:text-white px-3 py-2 rounded-xl transition-colors duration-200">
            Sign In
        </a>
        <a href="{{ route('register') }}"
           class="block bg-primary dark:bg-primary-700 hover:bg-primary-600 dark:hover:bg-primary-600 text-white px-3 py-2 mt-2 rounded-xl transition-all duration-200 text-center">
            Get Started <i class="fas fa-arrow-right ml-1 text-xs"></i>
        </a>
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

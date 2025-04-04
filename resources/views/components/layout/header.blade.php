<!-- Modern Header with Logo and Navigation Links -->
<header class="bg-gradient-to-r from-white py-4 to-gray-50 shadow-sm fixed w-full top-0 z-50 transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center">
            <!-- Logo with hover animation -->
            <div class="flex-shrink-0 transition-transform duration-300 hover:scale-105">
                <a href="{{ url('/') }}" class="flex items-center space-x-2" aria-label="Relaticle Home">
                    <img class="h-12 w-auto" src="{{ asset('relaticle-logo.svg') }}" alt="Relaticle Logo">
                    <span class="font-bold text-lg text-[#4841D5] hidden sm:block">Relaticle</span>
                </a>
            </div>

            <!-- Desktop Navigation Links - Centered -->
            <nav class="hidden md:flex flex-1 justify-center space-x-6 items-center">
                <a href="{{ url('/#features') }}"
                   class="text-gray-600 hover:text-primary transition-colors duration-200"
                   aria-label="Product features">Features</a>
                <a href="{{ route('documentation.index') }}"
                   class="text-gray-600 hover:text-primary transition-colors duration-200"
                   aria-label="Documentation">Documentation</a>
                <a href="https://github.com/Relaticle" target="_blank" rel="noopener"
                   class="text-gray-600 hover:text-primary transition-colors duration-200 flex items-center gap-1"
                   aria-label="GitHub Repository">
                    <i class="fab fa-github"></i><span>GitHub</span>
                </a>
            </nav>

            <!-- Auth Links -->
            <div class="hidden md:flex space-x-4 items-center">
                <a href="{{ route('login') }}"
                   class="text-gray-600 hover:text-primary transition-colors duration-200"
                   aria-label="Sign in to your account">Sign In</a>
                <a href="{{ route('register') }}"
                   class="bg-gradient-to-r from-primary to-primary/90 hover:from-primary-dark hover:to-primary
                   px-5 py-2.5 rounded-lg text-white font-medium shadow-sm hover:shadow-md transition-all duration-300"
                   aria-label="Create a new account">Get Started</a>
            </div>

            <!-- Mobile Menu Button with improved animation -->
            <div class="md:hidden">
                <button id="mobile-menu-button"
                        class="text-gray-600 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:ring-opacity-50 rounded-md p-1 transition-colors duration-200"
                        aria-label="Toggle mobile menu"
                        aria-expanded="false">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path id="menu-icon" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 8h16M4 16h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu with smooth animation -->
    <div id="mobile-menu"
         class="md:hidden hidden opacity-0 transform -translate-y-4 px-2 pt-2 pb-3 space-y-1 bg-white shadow-lg rounded-b-lg transition-all duration-300 ease-in-out">
        <a href="#features"
           class="block text-center text-gray-700 hover:bg-gray-50 hover:text-primary px-3 py-2 rounded-md transition-colors duration-200">
            Features
        </a>
        <a href="{{ route('documentation.index') }}"
           class="block text-center text-gray-700 hover:bg-gray-50 hover:text-primary px-3 py-2 rounded-md transition-colors duration-200">
            Documentation
        </a>
        <a href="https://github.com/Relaticle" target="_blank" rel="noopener"
           class="flex items-center justify-center gap-2 text-gray-700 hover:bg-gray-50 hover:text-primary px-3 py-2 rounded-md transition-colors duration-200">
            <i class="fab fa-github"></i><span>GitHub</span>
        </a>
        <a href="{{ route('login') }}"
           class="block text-gray-700 hover:bg-gray-50 hover:text-primary px-3 py-2 rounded-md transition-colors duration-200">
            Sign In
        </a>
        <a href="{{ route('register') }}"
           class="block bg-gradient-to-r from-primary to-primary/90 text-white px-3 py-2 mt-2 rounded-lg transition-all duration-200">
            Get Started
        </a>
    </div>
</header>

<!-- Add a spacer to prevent content from hiding behind fixed header -->
<div class="h-20"></div>

<!-- Mobile Menu Script -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
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
        }
    });
</script> 
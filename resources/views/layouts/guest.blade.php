<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Relaticle') }}</title>
    {{--<title>Relaticle - The Next-Generation Open-Source CRM Platform</title>--}}
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet"/>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Styles -->
    @livewireStyles

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="antialiased text-gray-800">

<!-- Modern Header with Logo and Navigation Links -->
<header class="bg-gradient-to-r from-white to-gray-50 shadow-sm fixed w-full top-0 z-50 transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-4">
            <!-- Logo with hover animation -->
            <div class="flex-shrink-0 transition-transform duration-300 hover:scale-105">
                <a href="{{ url('/') }}" class="flex items-center space-x-1" aria-label="Relaticle Home">
                    <img class="h-12 w-auto" src="{{ asset('relaticle-logo.svg') }}" alt="Relaticle Logo">
                    <span class="font-semibold text-lg text-[#4841D5] hidden sm:block">Relaticle</span>
                </a>
            </div>

            <!-- Desktop Navigation Links -->
            <nav class="hidden md:flex space-x-6 items-center">
                <a href="https://github.com/Relaticle" target="_blank" rel="noopener"
                   class="text-gray-600 hover:text-primary transition-colors duration-200 flex items-center gap-1"
                   aria-label="GitHub Repository">
                    <i class="fab fa-github"></i><span>GitHub</span>
                </a>
                <a href="{{ route('login') }}"
                   class="text-gray-600 hover:text-primary transition-colors duration-200"
                   aria-label="Sign in to your account">Sign In</a>
                <a href="{{ route('register') }}"
                   class="bg-gradient-to-r from-primary to-primary/90 hover:from-primary-dark hover:to-primary
                   px-5 py-2.5 rounded-lg text-white font-medium shadow-sm hover:shadow-md transition-all duration-300"
                   aria-label="Create a new account">Get Started</a>
            </nav>

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
        <a href="https://github.com/Relaticle" target="_blank" rel="noopener"
           class="flex items-center gap-2 text-gray-700 hover:bg-gray-50 hover:text-primary px-3 py-2 rounded-md transition-colors duration-200">
           <i class="fab fa-github"></i><span>GitHub</span>
        </a>
        <a href="{{ route('login') }}"
           class="block text-gray-700 hover:bg-gray-50 hover:text-primary px-3 py-2 rounded-md transition-colors duration-200">
           Sign In
        </a>
        <a href="{{ route('register') }}"
           class="block bg-gradient-to-r from-primary to-primary-dark text-white px-3 py-2 mt-2 rounded-lg transition-all duration-200">
           Get Started
        </a>
    </div>
</header>

<!-- Add a spacer to prevent content from hiding behind fixed header -->
<div class="h-20"></div>

<!-- Main Content -->
{{ $slot }}

<!-- Modern Footer -->
<footer class="bg-gradient-to-b from-gray-900 to-gray-900 text-white py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Company Info -->
            <div class="space-y-4">
                <div class="flex items-center space-x-2">
                    <img class="h-10 w-auto" src="{{ asset('relaticle-logo.svg') }}" alt="Relaticle Logo">
                    <span class="font-bold text-xl">Relaticle</span>
                </div>
                <p class="text-gray-300 text-sm">The Next-Generation Open-Source CRM Platform designed to streamline your customer relationships.</p>
                <div class="flex space-x-4 pt-2">
                    <a href="https://github.com/Relaticle" target="_blank" rel="noopener" class="text-gray-300 hover:text-white transition-colors duration-200" aria-label="GitHub">
                        <i class="fab fa-github text-xl"></i>
                    </a>
                    <a href="#" class="text-gray-300 hover:text-white transition-colors duration-200" aria-label="Twitter">
                        <i class="fab fa-twitter text-xl"></i>
                    </a>
                    <a href="#" class="text-gray-300 hover:text-white transition-colors duration-200" aria-label="LinkedIn">
                        <i class="fab fa-linkedin text-xl"></i>
                    </a>
                </div>
            </div>

            <!-- Quick Links -->
            <div>
                <h3 class="font-semibold text-lg mb-4 border-b border-gray-700 pb-2">Quick Links</h3>
                <ul class="space-y-2">
                    <li><a href="{{ url('/') }}" class="text-gray-300 hover:text-white transition-colors duration-200 flex items-center gap-2">
                        <i class="fas fa-home text-xs"></i> Home
                    </a></li>
                    <li><a href="{{ url('features') }}" class="text-gray-300 hover:text-white transition-colors duration-200 flex items-center gap-2">
                        <i class="fas fa-star text-xs"></i> Features
                    </a></li>
                    <li><a href="{{ url('pricing') }}" class="text-gray-300 hover:text-white transition-colors duration-200 flex items-center gap-2">
                        <i class="fas fa-tag text-xs"></i> Pricing
                    </a></li>
                    <li><a href="https://github.com/Relaticle" target="_blank" rel="noopener" class="text-gray-300 hover:text-white transition-colors duration-200 flex items-center gap-2">
                        <i class="fab fa-github text-xs"></i> GitHub
                    </a></li>
                </ul>
            </div>

            <!-- Contact & Legal -->
            <div>
                <h3 class="font-semibold text-lg mb-4 border-b border-gray-700 pb-2">Support & Legal</h3>
                <ul class="space-y-2">
                    <li><a href="{{ url('privacy-policy') }}" class="text-gray-300 hover:text-white transition-colors duration-200 flex items-center gap-2">
                        <i class="fas fa-shield-alt text-xs"></i> Privacy Policy
                    </a></li>
                    <li><a href="{{ url('terms-of-service') }}" class="text-gray-300 hover:text-white transition-colors duration-200 flex items-center gap-2">
                        <i class="fas fa-file-contract text-xs"></i> Terms of Service
                    </a></li>
                    <li><a href="mailto:manuk.minasyan1@gmail.com" class="text-gray-300 hover:text-white transition-colors duration-200 flex items-center gap-2">
                        <i class="fas fa-envelope text-xs"></i> Contact Us
                    </a></li>
                </ul>
            </div>
        </div>

        <div class="mt-8 pt-4 border-t border-gray-700 flex flex-col md:flex-row md:justify-between items-center text-sm">
            <p>&copy; 2025 Relaticle. All rights reserved.</p>
            <p class="mt-2 md:mt-0 text-gray-400">Made with <span class="text-red-500">♥</span> for open-source</p>
        </div>
    </div>
</footer>

<!-- Enhanced Mobile Menu Script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
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
                    header.classList.add('py-2', 'shadow');
                } else {
                    header.classList.remove('py-2', 'shadow');
                }
            });
        }
    });
</script>

@livewireScripts
</body>
</html>

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
</head>
<body class="antialiased text-gray-800">

<!-- Header with Logo and Navigation Links -->
<header class="bg-white shadow">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-2">
            <!-- Logo -->
            <div class="flex-shrink-0">
                <a href="{{ url('/') }}">
                    <!-- Placeholder for logo image -->
                    <img class="h-12 w-auto" src="{{ asset('relaticle-logo.svg') }}" alt="Relaticle Logo">
                </a>
            </div>
            <!-- Navigation Links -->
            <nav class="hidden md:flex space-x-8">
                <a href="https://github.com/Relaticle" target="_blank"
                   class="text-gray-500 hover:text-gray-900">GitHub</a>
                <a href="{{ route('login') }}" class="text-gray-500 hover:text-gray-900">Sign In</a>
                <a href="{{ route('register') }}" class="text-gray-500 hover:text-gray-900">Get Started</a>
            </nav>
            <!-- Mobile Menu Button -->
            <div class="md:hidden">
                <button id="mobile-menu-button" class="text-gray-500 hover:text-gray-900 focus:outline-none">
                    <!-- Mobile menu icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path id="menu-icon" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 8h16M4 16h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    <!-- Mobile Menu -->
    <div id="mobile-menu" class="md:hidden hidden px-2 pt-2 pb-3 space-y-1">
        <a href="https://github.com/Relaticle" target="_blank"
           class="block text-gray-700 hover:bg-gray-100 px-3 py-2 rounded-md">GitHub</a>
        <a href="{{ route('login') }}" class="block text-gray-700 hover:bg-gray-100 px-3 py-2 rounded-md">Sign In</a>
        <a href="{{ route('register') }}" class="block text-gray-700 hover:bg-gray-100 px-3 py-2 rounded-md">Get
            Started</a>
    </div>
</header>

<!-- Main Content -->
{{ $slot }}


<!-- Footer -->
<footer class="bg-gray-800 text-white py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:justify-between items-center">
            <p>&copy; 2024 Relaticle. All rights reserved.</p>
            <div class="mt-4 md:mt-0">
                <a href="{{ url('privacy-policy') }}" class="text-gray-300 hover:text-white mx-2">Privacy Policy</a>
                <a href="{{ url('terms-of-service') }}" class="text-gray-300 hover:text-white mx-2">Terms of Service</a>
                <a href="mailto:manuk.minasyan1@gmail.com" class="text-gray-300 hover:text-white mx-2">Contact Us</a>
            </div>
        </div>
    </div>
</footer>

<!-- Mobile Menu Script -->
<script>
    const menuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    const menuIcon = document.getElementById('menu-icon');

    menuButton.addEventListener('click', () => {
        mobileMenu.classList.toggle('hidden');
        // Toggle menu icon between hamburger and X
        if (mobileMenu.classList.contains('hidden')) {
            menuIcon.setAttribute('d', 'M4 8h16M4 16h16');
        } else {
            menuIcon.setAttribute('d', 'M6 18L18 6M6 6l12 12');
        }
    });
</script>

@livewireScripts
</body>
</html>

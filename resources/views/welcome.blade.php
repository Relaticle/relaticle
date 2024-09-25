<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Relaticle - The Next-Generation Open-Source CRM Platform</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Custom Tailwind Configuration -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#28B6E4',
                    },
                    keyframes: {
                        fadeInUp: {
                            '0%': {opacity: '0', transform: 'translateY(20px)'},
                            '100%': {opacity: '1', transform: 'translateY(0)'},
                        },
                    },
                    animation: {
                        fadeInUp: 'fadeInUp 1s ease-out forwards',
                    },
                },
            },
        }
    </script>
</head>
<body class="antialiased text-gray-800">

<!-- Header with Logo and Navigation Links -->
<header class="bg-white shadow">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-6">
            <!-- Logo -->
            <div class="flex-shrink-0">
                <a href="#">
                    <!-- Placeholder for logo image -->
                    <img class="h-14 w-auto" src="{{ asset('relaticle-logo.svg') }}" alt="Relaticle Logo">
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

<!-- Hero Section -->
<section class="bg-white">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <h1 class="text-6xl leading-20 font-bold text-center text-primary">
            The Next-Generation <br/> Open-Source CRM Platform
        </h1>
        <p class="text-center text-2xl text-[#6D6E71] mt-3">
            Transforming Client Relationship Management with Innovation and Efficiency
        </p>
        <div class="mt-8 flex justify-center">
            <a href="{{ route('register') }}"
               class="bg-primary text-white px-8 py-4 rounded-md text-lg font-medium hover:bg-opacity-90 transition">
                Get Started
            </a>
        </div>
        <!-- App Preview Image with Animation -->
        <div class="mt-12 flex justify-center">
            <img src="{{ asset('images/app-preview.png') }}" alt="App Preview"
                 class="w-full border shadow-2xl rounded max-w-3xl animate-fadeInUp">
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="bg-gray-50 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-center text-gray-800">
            Features
        </h2>
        <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-12">
            <!-- Feature 1 -->
            <div class="text-center">
                <div class="bg-primary text-white w-20 h-20 mx-auto rounded-full flex items-center justify-center">
                    <!-- Icon placeholder -->
                    <span class="text-3xl">🌐</span>
                </div>
                <h3 class="mt-6 text-xl font-semibold">Seamless Integration</h3>
                <p class="mt-4 text-gray-600">Easily integrate with your existing tools and platforms.</p>
            </div>
            <!-- Feature 2 -->
            <div class="text-center">
                <div class="bg-primary text-white w-20 h-20 mx-auto rounded-full flex items-center justify-center">
                    <!-- Icon placeholder -->
                    <span class="text-3xl">⚡</span>
                </div>
                <h3 class="mt-6 text-xl font-semibold">Lightning Fast</h3>
                <p class="mt-4 text-gray-600">Experience unmatched performance and speed.</p>
            </div>
            <!-- Feature 3 -->
            <div class="text-center">
                <div class="bg-primary text-white w-20 h-20 mx-auto rounded-full flex items-center justify-center">
                    <!-- Icon placeholder -->
                    <span class="text-3xl">🔒</span>
                </div>
                <h3 class="mt-6 text-xl font-semibold">Secure and Reliable</h3>
                <p class="mt-4 text-gray-600">Your data is protected with top-notch security measures.</p>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="bg-gray-800 text-white py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:justify-between items-center">
            <p>&copy; 2024 Relaticle. All rights reserved.</p>
            <div class="mt-4 md:mt-0">
                <a href="#" class="text-gray-300 hover:text-white mx-2">Privacy Policy</a>
                <a href="#" class="text-gray-300 hover:text-white mx-2">Terms of Service</a>
                <a href="#" class="text-gray-300 hover:text-white mx-2">Contact Us</a>
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

</body>
</html>

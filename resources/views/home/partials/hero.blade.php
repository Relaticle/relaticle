<!-- Modern Minimalist Hero Section -->
<section class="relative py-20 md:py-28 lg:py-36 bg-white dark:bg-black">
    <!-- Extremely subtle grid pattern -->
    <div class="absolute inset-0 bg-grid-pattern opacity-[0.01] dark:opacity-[0.02]"></div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="space-y-10 md:space-y-12">
            <!-- Tech Stack Badge - Simplified -->
            <div class="flex justify-center">
                <div class="inline-flex items-center space-x-4 px-3 py-1 border border-gray-100 dark:border-gray-800 rounded-full text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Built with</span>
                    <div class="flex items-center gap-1.5">
                        <img src="https://laravel.com/img/logomark.min.svg" alt="Laravel" class="h-4 w-4">
                        <span class="font-medium text-gray-700 dark:text-gray-300">Laravel</span>
                    </div>
                    <span class="text-gray-300 dark:text-gray-600">·</span>
                    <div class="flex items-center gap-1.5">
                        <img src="https://filamentphp.com/favicon/safari-pinned-tab.svg" alt="Filament" class="h-4 w-4">
                        <span class="font-medium text-gray-700 dark:text-gray-300">Filament</span>
                    </div>
                    <span class="text-gray-300 dark:text-gray-600">·</span>
                    <div class="flex items-center gap-1.5">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary/75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-primary"></span>
                        </span>
                        <span class="font-medium text-primary dark:text-primary-400">Open Source</span>
                    </div>
                </div>
            </div>

            <!-- Hero Text - Improved Typography -->
            <div class="text-center space-y-6 max-w-3xl mx-auto">
                <h1 class="text-4xl sm:text-5xl md:text-6xl font-bold text-black dark:text-white leading-[1.1] tracking-tight">
                    The Next-Generation<br class="hidden sm:block"/> Open-Source CRM
                </h1>

                <p class="text-lg md:text-xl text-gray-600 dark:text-gray-300 max-w-2xl mx-auto leading-relaxed">
                    Transforming client relationship management with a modern, open-source approach. Built for businesses that value simplicity and efficiency.
                </p>
            </div>

            <!-- CTA Section - Cleaner -->
            <div class="flex flex-col sm:flex-row justify-center items-center gap-4 sm:gap-5">
                <a href="{{ route('register') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-black hover:bg-gray-800 dark:bg-white dark:hover:bg-gray-100 text-white dark:text-black px-6 py-3 rounded-md text-base font-medium transition-colors duration-200">
                    Get Started
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>

                <a href="https://github.com/relaticle/relaticle" target="_blank" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 rounded-md text-gray-600 dark:text-gray-300 hover:text-black dark:hover:text-white border border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700 transition-colors duration-200">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.237 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                    </svg>
                    GitHub
                </a>
            </div>

            <!-- App Preview - Refined Minimalism -->
            <div class="mt-16 md:mt-20 max-w-5xl mx-auto">
                <div class="bg-white dark:bg-gray-900 rounded-lg overflow-hidden border border-gray-100 dark:border-gray-800 shadow-sm dark:shadow-gray-950/20">
                    <!-- Browser Header -->
                    <div class="bg-gray-50 dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 px-4 py-2 flex items-center">
                        <!-- Window Controls -->
                        <div class="flex space-x-1.5">
                            <div class="w-2.5 h-2.5 rounded-full bg-gray-300 dark:bg-gray-600"></div>
                            <div class="w-2.5 h-2.5 rounded-full bg-gray-300 dark:bg-gray-600"></div>
                            <div class="w-2.5 h-2.5 rounded-full bg-gray-300 dark:bg-gray-600"></div>
                        </div>

                        <!-- Browser Address Bar -->
                        <div class="ml-4 flex-1 bg-white dark:bg-gray-700 rounded-md px-3 py-1 text-xs text-gray-600 dark:text-gray-300 flex items-center">
                            <span>app.relaticle.com/dashboard</span>
                        </div>
                    </div>

                    <!-- Browser Content Area -->
                    <div class="relative">
                        <picture>
                            <!-- Dark mode image -->
                            <source media="(prefers-color-scheme: dark)" srcset="{{ asset('images/app-preview-dark.png') }}">
                            <!-- Default light mode image -->
                            <img src="{{ asset('images/app-preview.png') }}" alt="Relaticle CRM Dashboard" class="w-full h-auto" width="1200" height="675" loading="lazy">
                        </picture>
                    </div>
                </div>
            </div>

            <!-- Key Highlights - Simpler -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-5 mt-12 md:mt-16 border-t border-gray-100 dark:border-gray-800 pt-10">
                <div class="p-4 text-center">
                    <div class="text-lg md:text-xl font-bold text-black dark:text-white">Open Source</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Free to use and modify</div>
                </div>
                <div class="p-4 text-center">
                    <div class="text-lg md:text-xl font-bold text-black dark:text-white">Modern Stack</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">PHP 8.3, Laravel 12</div>
                </div>
                <div class="p-4 text-center">
                    <div class="text-lg md:text-xl font-bold text-black dark:text-white">Secure</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Enterprise-grade security</div>
                </div>
                <div class="p-4 text-center">
                    <div class="text-lg md:text-xl font-bold text-black dark:text-white">Scalable</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Grows with your business</div>
                </div>
            </div>
        </div>
    </div>
</section>

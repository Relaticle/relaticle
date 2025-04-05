<!-- Modern Minimalist Hero Section -->
<section class="relative py-20 md:py-28 lg:py-36 bg-white dark:bg-black overflow-hidden">
    <!-- Subtle background elements -->
    <div class="absolute inset-0 bg-grid-pattern opacity-[0.01] dark:opacity-[0.02]"></div>
    <div class="absolute -top-24 -left-24 w-96 h-96 bg-primary/5 dark:bg-primary/10 rounded-full blur-3xl"></div>
    <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-primary/5 dark:bg-primary/10 rounded-full blur-3xl"></div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="space-y-14 md:space-y-16">
            <!-- Tech Badge - Simplified -->
            <div class="flex justify-center">
                <div class="inline-flex items-center px-3 py-1.5 border border-gray-100 dark:border-gray-800 rounded-full text-xs shadow-sm">
                    <span class="text-gray-500 dark:text-gray-400 mr-2">Built with</span>
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1">
                            <img src="https://laravel.com/img/logomark.min.svg" alt="Laravel" class="h-3.5 w-3.5">
                            <span class="font-medium text-gray-700 dark:text-gray-300">Laravel</span>
                        </div>
                        <span class="text-gray-300 dark:text-gray-600">·</span>
                        <div class="flex items-center gap-1">
                            <img src="https://filamentphp.com/favicon/safari-pinned-tab.svg" alt="Filament" class="h-3.5 w-3.5">
                            <span class="font-medium text-gray-700 dark:text-gray-300">Filament</span>
                        </div>
                        <span class="text-gray-300 dark:text-gray-600">·</span>
                        <div class="flex items-center gap-1">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary/75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-primary"></span>
                            </span>
                            <span class="font-medium text-primary dark:text-primary-400">Open Source</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hero Text - Enhanced Typography -->
            <div class="text-center space-y-6 max-w-3xl mx-auto">
                <h1 class="text-4xl sm:text-5xl md:text-6xl font-bold text-black dark:text-white leading-[1.1] tracking-tight">
                    The Next-Generation<br class="hidden sm:block"/> <span class="relative inline-block">
                        <span class="relative z-10">Open-Source CRM</span>
                        <span class="absolute bottom-2 left-0 w-full h-3 bg-primary/10 dark:bg-primary/20 -rotate-1 z-0"></span>
                    </span>
                </h1>

                <p class="text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto leading-relaxed">
                    Transforming client relationship management with a modern, intuitive approach. Built for businesses that value simplicity and efficiency.
                </p>
            </div>

            <!-- CTA Section - Refined -->
            <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
                <a href="{{ route('register') }}" class="group w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary-600 text-white px-7 py-3.5 rounded-md text-base font-medium transition-all duration-200 shadow-sm hover:shadow">
                    <span>Get Started</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>

                <a href="https://github.com/relaticle/relaticle" target="_blank" class="group w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-md text-gray-600 dark:text-gray-300 hover:text-black dark:hover:text-white border border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700 transition-all duration-200 bg-white/50 dark:bg-black/50">
                    <svg class="w-5 h-5 transition-transform duration-300 group-hover:scale-110" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.237 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                    </svg>
                    <span class="font-medium">GitHub</span>
                </a>
            </div>

            <!-- App Preview - Cleaner Mockup -->
            <div class="mt-16 md:mt-20 max-w-5xl mx-auto">
                <div class="bg-white dark:bg-gray-900 rounded-lg overflow-hidden border border-gray-100 dark:border-gray-800 shadow-lg dark:shadow-gray-950/20 hover:shadow-xl transition-shadow duration-300">
                    <!-- Browser Header -->
                    <div class="bg-gray-50 dark:bg-gray-800/80 border-b border-gray-100 dark:border-gray-700 px-4 py-2 flex items-center">
                        <!-- Window Controls -->
                        <div class="flex space-x-1.5">
                            <div class="w-3 h-3 rounded-full bg-gray-300/80 dark:bg-gray-600/80"></div>
                            <div class="w-3 h-3 rounded-full bg-gray-300/80 dark:bg-gray-600/80"></div>
                            <div class="w-3 h-3 rounded-full bg-gray-300/80 dark:bg-gray-600/80"></div>
                        </div>

                        <!-- Browser Address Bar -->
                        <div class="ml-4 flex-1 bg-white/90 dark:bg-gray-700/80 rounded-md px-3 py-1 text-xs text-gray-600 dark:text-gray-300 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-green-500 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
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

                        <!-- Subtle highlight overlay -->
                        <div class="absolute inset-0 bg-gradient-to-tr from-primary/5 via-transparent to-transparent opacity-60 pointer-events-none"></div>
                    </div>
                </div>
            </div>

            <!-- Key Highlights - Simplified -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mt-14 border-t border-gray-100 dark:border-gray-800 pt-10 px-2">
                <div class="p-3 text-center">
                    <div class="text-lg font-semibold text-black dark:text-white">Open Source</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Free to use and customize</div>
                </div>
                <div class="p-3 text-center">
                    <div class="text-lg font-semibold text-black dark:text-white">Modern Stack</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">PHP 8.3, Laravel 12</div>
                </div>
                <div class="p-3 text-center">
                    <div class="text-lg font-semibold text-black dark:text-white">Secure</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Enterprise-grade security</div>
                </div>
                <div class="p-3 text-center">
                    <div class="text-lg font-semibold text-black dark:text-white">Scalable</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Grows with your business</div>
                </div>
            </div>
        </div>
    </div>
</section>

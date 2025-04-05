<!-- Modern Footer with 2025 Design Trends -->
<footer class="relative overflow-hidden bg-gradient-to-b from-gray-50 via-white to-gray-50 dark:from-gray-900 dark:via-gray-900 dark:to-black pt-20 pb-10">
    <!-- Background effects -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <!-- Gradient blob decorations -->
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-primary opacity-5 dark:opacity-10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-indigo-600 opacity-5 dark:opacity-10 rounded-full blur-3xl"></div>
        
        <!-- Subtle grid pattern -->
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjAuNSIgZmlsbD0iY3VycmVudENvbG9yIi8+PC9zdmc+')] bg-[length:60px_60px] text-gray-200 dark:text-white opacity-[0.03] dark:opacity-[0.03]"></div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-12 pb-16 border-b border-gray-200 dark:border-gray-800">
            <!-- Company Info -->
            <div class="md:col-span-5 space-y-6">
                <div class="flex items-center space-x-3">
                    <img class="h-10 w-auto" src="{{ asset('relaticle-logo.svg') }}" alt="Relaticle Logo">
                    <span class="font-bold text-2xl bg-clip-text text-transparent bg-gradient-to-r from-gray-800 to-gray-600 dark:from-white dark:to-gray-400">Relaticle</span>
                </div>
                <p class="text-gray-600 dark:text-gray-400 text-base leading-relaxed max-w-md">
                    The Next-Generation Open-Source CRM Platform designed to streamline your customer relationships. Built with modern technologies for businesses of all sizes.
                </p>
                
                <!-- Social links with improved design -->
                <div class="flex space-x-5 pt-2">
                    <a href="https://github.com/Relaticle" target="_blank" rel="noopener"
                       class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors duration-200 group" aria-label="GitHub">
                        <span class="sr-only">GitHub</span>
                        <div class="w-10 h-10 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded-full flex items-center justify-center group-hover:ring-2 group-hover:ring-primary/30 transition-all duration-300">
                            <i class="fab fa-github text-xl"></i>
                        </div>
                    </a>
                    <a href="#" class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors duration-200 group" aria-label="Twitter">
                        <span class="sr-only">Twitter</span>
                        <div class="w-10 h-10 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded-full flex items-center justify-center group-hover:ring-2 group-hover:ring-primary/30 transition-all duration-300">
                            <i class="fab fa-twitter text-xl"></i>
                        </div>
                    </a>
                    <a href="#" class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors duration-200 group" aria-label="LinkedIn">
                        <span class="sr-only">LinkedIn</span>
                        <div class="w-10 h-10 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded-full flex items-center justify-center group-hover:ring-2 group-hover:ring-primary/30 transition-all duration-300">
                            <i class="fab fa-linkedin text-xl"></i>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Quick Links Column -->
            <div class="md:col-span-3">
                <h3 class="font-semibold text-lg text-gray-900 dark:text-white mb-6 pb-2 border-b border-gray-200 dark:border-gray-800 inline-block">
                    Quick Links
                </h3>
                <ul class="space-y-4">
                    <li>
                        <a href="{{ url('/') }}"
                           class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors duration-200 flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-gray-600 group-hover:bg-primary transition-colors duration-200"></span> 
                            <span>Home</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('documentation.index') }}"
                           class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors duration-200 flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-gray-600 group-hover:bg-primary transition-colors duration-200"></span>
                            <span>Documentation</span>
                        </a>
                    </li>
                    <li>
                        <a href="https://github.com/Relaticle" target="_blank" rel="noopener"
                           class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors duration-200 flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-gray-600 group-hover:bg-primary transition-colors duration-200"></span>
                            <span>GitHub</span>
                        </a>
                    </li>
                    <li>
                        <a href="#features"
                           class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors duration-200 flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-gray-600 group-hover:bg-primary transition-colors duration-200"></span>
                            <span>Features</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Legal Links Column -->
            <div class="md:col-span-4">
                <h3 class="font-semibold text-lg text-gray-900 dark:text-white mb-6 pb-2 border-b border-gray-200 dark:border-gray-800 inline-block">
                    Support & Legal
                </h3>
                <ul class="space-y-4">
                    <li>
                        <a href="{{ url('privacy-policy') }}"
                           class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors duration-200 flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-gray-600 group-hover:bg-primary transition-colors duration-200"></span>
                            <span>Privacy Policy</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('terms-of-service') }}"
                           class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors duration-200 flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-gray-600 group-hover:bg-primary transition-colors duration-200"></span>
                            <span>Terms of Service</span>
                        </a>
                    </li>
                    <li>
                        <a href="mailto:manuk.minasyan1@gmail.com"
                           class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors duration-200 flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-gray-600 group-hover:bg-primary transition-colors duration-200"></span>
                            <span>Contact Us</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Copyright section with improved design -->
        <div class="mt-8 flex flex-col md:flex-row md:justify-between items-center text-sm">
            <p class="text-gray-600 dark:text-gray-500">&copy; {{ date('Y') }} Relaticle. All rights reserved.</p>
            <div class="mt-4 md:mt-0 flex items-center space-x-2">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-primary-500"></span>
                </span>
                <p class="text-gray-600 dark:text-gray-500">Made with <span class="text-red-500">♥</span> for open-source</p>
            </div>
        </div>
    </div>
</footer> 
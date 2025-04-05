<!-- Minimalist Footer -->
<footer class="py-16 md:py-20 border-t border-gray-100 dark:border-gray-900 bg-white dark:bg-black">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-12 md:gap-8 pb-12 border-b border-gray-100 dark:border-gray-900">
            <!-- Company Info -->
            <div class="md:col-span-5 space-y-6">
                <div class="flex items-center space-x-3">
                    <div class="relative overflow-hidden p-0.5">
                        <img class="h-9 w-auto" src="{{ asset('relaticle-logo.svg') }}" alt="Relaticle Logo">
                    </div>
                    <span class="font-bold text-xl text-black dark:text-white">Relaticle</span>
                </div>
                <p class="text-gray-500 dark:text-gray-400 text-base leading-relaxed max-w-md">
                    The Next-Generation Open-Source CRM Platform designed to streamline your customer relationships. Built with modern technologies for businesses of all sizes.
                </p>
                
                <!-- Social links - Enhanced -->
                <div class="flex space-x-5">
                    <a href="https://github.com/Relaticle" target="_blank" rel="noopener"
                       class="text-gray-400 hover:text-black dark:hover:text-white transition-colors" aria-label="GitHub">
                        <span class="sr-only">GitHub</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.237 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                        </svg>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-black dark:hover:text-white transition-colors" aria-label="Twitter">
                        <span class="sr-only">Twitter</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723 10.03 10.03 0 01-3.127 1.184A4.92 4.92 0 0016.687 2a4.935 4.935 0 00-4.928 4.928c0 .388.046.765.125 1.124A13.98 13.98 0 012.172 3.176a4.92 4.92 0 001.52 6.575 4.868 4.868 0 01-2.228-.616v.06a4.935 4.935 0 003.95 4.826 4.929 4.929 0 01-2.224.086 4.935 4.935 0 004.602 3.417 9.875 9.875 0 01-6.102 2.105c-.39 0-.78-.023-1.17-.067a14.015 14.015 0 007.543 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63a9.93 9.93 0 002.46-2.548z"/>
                        </svg>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-black dark:hover:text-white transition-colors" aria-label="LinkedIn">
                        <span class="sr-only">LinkedIn</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Quick Links Column - Enhanced -->
            <div class="md:col-span-3">
                <h3 class="font-medium text-sm text-black dark:text-white uppercase tracking-wider mb-5">
                    Quick Links
                </h3>
                <ul class="space-y-4">
                    <li>
                        <a href="{{ url('/') }}" class="group flex items-center text-gray-500 dark:text-gray-400 hover:text-black dark:hover:text-white text-sm transition-colors">
                            <span>Home</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 ml-1 opacity-0 group-hover:opacity-100 transform translate-x-0 group-hover:translate-x-1 transition-all duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('documentation.index') }}" class="group flex items-center text-gray-500 dark:text-gray-400 hover:text-black dark:hover:text-white text-sm transition-colors">
                            <span>Documentation</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 ml-1 opacity-0 group-hover:opacity-100 transform translate-x-0 group-hover:translate-x-1 transition-all duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a href="https://github.com/Relaticle" target="_blank" rel="noopener" class="group flex items-center text-gray-500 dark:text-gray-400 hover:text-black dark:hover:text-white text-sm transition-colors">
                            <span>GitHub</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 ml-1 opacity-0 group-hover:opacity-100 transform translate-x-0 group-hover:translate-x-1 transition-all duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a href="#features" class="group flex items-center text-gray-500 dark:text-gray-400 hover:text-black dark:hover:text-white text-sm transition-colors">
                            <span>Features</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 ml-1 opacity-0 group-hover:opacity-100 transform translate-x-0 group-hover:translate-x-1 transition-all duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Legal Links Column - Enhanced -->
            <div class="md:col-span-4">
                <h3 class="font-medium text-sm text-black dark:text-white uppercase tracking-wider mb-5">
                    Support & Legal
                </h3>
                <ul class="space-y-4">
                    <li>
                        <a href="{{ url('privacy-policy') }}" class="group flex items-center text-gray-500 dark:text-gray-400 hover:text-black dark:hover:text-white text-sm transition-colors">
                            <span>Privacy Policy</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 ml-1 opacity-0 group-hover:opacity-100 transform translate-x-0 group-hover:translate-x-1 transition-all duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('terms-of-service') }}" class="group flex items-center text-gray-500 dark:text-gray-400 hover:text-black dark:hover:text-white text-sm transition-colors">
                            <span>Terms of Service</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 ml-1 opacity-0 group-hover:opacity-100 transform translate-x-0 group-hover:translate-x-1 transition-all duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a href="mailto:manuk.minasyan1@gmail.com" class="group flex items-center text-gray-500 dark:text-gray-400 hover:text-black dark:hover:text-white text-sm transition-colors">
                            <span>Contact Us</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 ml-1 opacity-0 group-hover:opacity-100 transform translate-x-0 group-hover:translate-x-1 transition-all duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Copyright section - Refined -->
        <div class="mt-10 flex flex-col md:flex-row md:justify-between items-center">
            <p class="text-gray-500 dark:text-gray-400 text-sm">&copy; {{ date('Y') }} Relaticle. All rights reserved.</p>
            <div class="mt-5 md:mt-0 flex items-center text-sm text-gray-500 dark:text-gray-400">
                Made with <span class="text-red-500 mx-1.5">♥</span> for open-source
            </div>
        </div>
    </div>
</footer> 
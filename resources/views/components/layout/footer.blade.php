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
                <p class="text-gray-300 text-sm">The Next-Generation Open-Source CRM Platform designed to streamline
                    your customer relationships.</p>
                <div class="flex space-x-4 pt-2">
                    <a href="https://github.com/Relaticle" target="_blank" rel="noopener"
                       class="text-gray-300 hover:text-white transition-colors duration-200" aria-label="GitHub">
                        <i class="fab fa-github text-xl"></i>
                    </a>
                    <a href="#" class="text-gray-300 hover:text-white transition-colors duration-200"
                       aria-label="Twitter">
                        <i class="fab fa-twitter text-xl"></i>
                    </a>
                    <a href="#" class="text-gray-300 hover:text-white transition-colors duration-200"
                       aria-label="LinkedIn">
                        <i class="fab fa-linkedin text-xl"></i>
                    </a>
                </div>
            </div>

            <!-- Quick Links -->
            <div>
                <h3 class="font-semibold text-lg mb-4 border-b border-gray-700 pb-2">Quick Links</h3>
                <ul class="space-y-2">
                    <li>
                        <a href="{{ url('/') }}"
                           class="text-gray-300 hover:text-white transition-colors duration-200 flex items-center gap-2">
                            <i class="fas fa-home text-xs"></i> Home
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('documentation.index') }}"
                           class="text-gray-300 hover:text-white transition-colors duration-200 flex items-center gap-2">
                            <i class="fas fa-book text-xs"></i> Documentation
                        </a>
                    </li>
                    <li>
                        <a href="https://github.com/Relaticle" target="_blank" rel="noopener"
                           class="text-gray-300 hover:text-white transition-colors duration-200 flex items-center gap-2">
                            <i class="fab fa-github text-xs"></i> GitHub
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Contact & Legal -->
            <div>
                <h3 class="font-semibold text-lg mb-4 border-b border-gray-700 pb-2">Support & Legal</h3>
                <ul class="space-y-2">
                    <li><a href="{{ url('privacy-policy') }}"
                           class="text-gray-300 hover:text-white transition-colors duration-200 flex items-center gap-2">
                            <i class="fas fa-shield-alt text-xs"></i> Privacy Policy
                        </a></li>
                    <li><a href="{{ url('terms-of-service') }}"
                           class="text-gray-300 hover:text-white transition-colors duration-200 flex items-center gap-2">
                            <i class="fas fa-file-contract text-xs"></i> Terms of Service
                        </a></li>
                    <li><a href="mailto:manuk.minasyan1@gmail.com"
                           class="text-gray-300 hover:text-white transition-colors duration-200 flex items-center gap-2">
                            <i class="fas fa-envelope text-xs"></i> Contact Us
                        </a></li>
                </ul>
            </div>
        </div>

        <div
            class="mt-8 pt-4 border-t border-gray-700 flex flex-col md:flex-row md:justify-between items-center text-sm">
            <p>&copy; 2025 Relaticle. All rights reserved.</p>
            <p class="mt-2 md:mt-0 text-gray-400">Made with <span class="text-red-500">♥</span> for open-source</p>
        </div>
    </div>
</footer> 
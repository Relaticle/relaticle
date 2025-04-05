<!-- Modern Minimalist Features Section -->
<section id="features" class="py-24 md:py-32 bg-white dark:bg-black border-t border-gray-100 dark:border-gray-900 relative overflow-hidden">
    <!-- Subtle gradient blob -->
    <div class="absolute -bottom-64 left-0 w-96 h-96 bg-primary/5 dark:bg-primary/10 rounded-full blur-3xl"></div>
    
    <div class="container max-w-6xl mx-auto px-6 lg:px-8 relative">
        <!-- Section Header -->
        <div class="max-w-3xl mx-auto text-center mb-16 md:mb-24">
            <span class="inline-block px-3 py-1 bg-gray-50 dark:bg-gray-900 rounded-full text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">
                Features
            </span>
            <h2 class="mt-4 text-3xl sm:text-4xl font-bold text-black dark:text-white">
                Everything you need to manage relationships
            </h2>
            <p class="mt-5 text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto leading-relaxed">
                A comprehensive suite of tools designed to streamline your client management workflow
            </p>
        </div>

        <!-- Features Grid - Clean & Minimal -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 md:gap-10">
            @php
                $features = [
                    [
                        'title' => 'Task Management',
                        'description' => 'Stay on top of your team\'s productivity with easy task creation, assignment, and tracking. Receive real-time notifications to keep everyone updated.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />'
                    ],
                    [
                        'title' => 'People Management',
                        'description' => 'Effortlessly manage individual contacts with detailed profiles, advanced search options, and complete interaction histories to personalize your outreach.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />'
                    ],
                    [
                        'title' => 'Company Management',
                        'description' => 'Maintain detailed company profiles and link them to individual contacts, track opportunities, and manage tasks for seamless business operations.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />'
                    ],
                    [
                        'title' => 'Sales Opportunities',
                        'description' => 'Visualize and manage your sales pipeline with custom stages, lifecycle tracking, and detailed outcome analysis to drive your sales process forward.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />'
                    ],
                    [
                        'title' => 'Notes & Organization',
                        'description' => 'Capture and categorize notes linked to people, companies, and tasks. Share updates with your team and quickly retrieve important information.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />'
                    ],
                    [
                        'title' => 'Customizable Data Model',
                        'description' => 'Tailor the CRM to your business needs with user and system-defined custom fields, dynamic forms, and validation rules for data integrity.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />'
                    ],
                ];
            @endphp

            @foreach ($features as $feature)
                <div class="group relative bg-white dark:bg-black border border-gray-100 dark:border-gray-800 rounded-xl p-6 sm:p-8 transition duration-300 hover:border-gray-200 dark:hover:border-gray-700 hover:shadow-sm">
                    <div class="mb-6 flex items-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-gray-50 dark:bg-gray-900 text-black dark:text-white group-hover:bg-primary/10 dark:group-hover:bg-primary/20 transition-colors duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                {!! $feature['icon'] !!}
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-xl font-semibold text-black dark:text-white mb-3 group-hover:text-primary dark:group-hover:text-primary-400 transition-colors duration-300">
                        {{ $feature['title'] }}
                    </h3>
                    <p class="text-gray-600 dark:text-gray-300 text-base leading-relaxed">
                        {{ $feature['description'] }}
                    </p>
                    
                    <!-- Subtle visual indicator for interaction -->
                    <div class="mt-5 flex items-center text-sm text-gray-500 dark:text-gray-400 font-medium opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <span>Learn more</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1 transition-transform duration-300 group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Call-to-action - Refined -->
        <div class="mt-24 text-center">
            <div class="inline-block pt-10 px-6 md:px-16 border-t border-gray-100 dark:border-gray-800 max-w-3xl mx-auto">
                <h3 class="text-2xl font-bold text-black dark:text-white mb-4">Ready to transform your customer relationships?</h3>
                <p class="text-gray-600 dark:text-gray-300 mb-8 text-lg max-w-xl mx-auto">Experience the power of Relaticle CRM today with a free account.</p>
                <a href="{{ route('register') }}" class="group inline-flex items-center justify-center gap-2 bg-black hover:bg-gray-900 dark:bg-white dark:hover:bg-gray-50 text-white dark:text-black px-8 py-4 rounded-md font-medium text-base transition-all duration-300 relative overflow-hidden shadow-sm hover:shadow">
                    <span class="relative z-10">Get Started Free</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-transform duration-300 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                    <span class="absolute inset-0 bg-gradient-to-r from-gray-800 to-black dark:from-gray-200 dark:to-white opacity-0 group-hover:opacity-100 transition-opacity duration-300"></span>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Modern Features Section with 2025 Design Trends -->
<section id="features"
         class="py-24 bg-gradient-to-b from-white to-slate-50 dark:from-gray-900 dark:to-gray-950 relative overflow-hidden">
    <!-- Decorative elements with dark mode support -->
    <div class="absolute inset-0 overflow-hidden">
        <div
            class="absolute top-1/2 right-0 w-96 h-96 bg-blue-100 dark:bg-blue-900 opacity-40 dark:opacity-10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>
        <div
            class="absolute bottom-0 left-1/4 w-64 h-64 bg-indigo-100 dark:bg-indigo-900 opacity-30 dark:opacity-10 rounded-full blur-3xl"></div>

        <!-- Modern mesh gradient for 2025 design -->
        <div
            class="absolute inset-0 bg-[radial-gradient(at_top_left,_var(--tw-gradient-stops))] from-blue-50 via-transparent to-transparent dark:from-blue-950 dark:via-transparent dark:to-transparent opacity-30 dark:opacity-20"></div>
    </div>

    <div class="container max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
        <!-- Section Header with improved typography -->
        <div class="max-w-3xl mx-auto text-center mb-16 md:mb-24">
            <span
                class="inline-block px-4 py-1.5 text-sm font-medium bg-gradient-to-r from-indigo-500/10 to-purple-500/10 dark:from-indigo-500/20 dark:to-purple-500/20 text-indigo-600 dark:text-indigo-400 rounded-full backdrop-blur-sm mb-4 border border-indigo-100 dark:border-indigo-900/30">
                Powerful Features
            </span>
            <h2 class="text-4xl font-bold text-gray-900 dark:text-white md:text-5xl lg:text-6xl">
                Everything you need to <span
                    class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-indigo-600 dark:from-primary-400 dark:to-indigo-400">manage relationships</span>
            </h2>
            <p class="mt-6 text-xl text-gray-600 dark:text-gray-300 font-light">
                A comprehensive suite of tools to streamline your client management workflow
            </p>
        </div>

        <!-- Features Grid with modern card design -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 lg:gap-10">
            @php
                $features = [
                    [
                        'title' => 'Task Management',
                        'description' => 'Stay on top of your team\'s productivity with easy task creation, assignment, and tracking. Receive real-time notifications to keep everyone updated.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />'
                    ],
                    [
                        'title' => 'People Management',
                        'description' => 'Effortlessly manage individual contacts with detailed profiles, advanced search options, and complete interaction histories to personalize your outreach.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />'
                    ],
                    [
                        'title' => 'Company Management',
                        'description' => 'Maintain detailed company profiles and link them to individual contacts, track opportunities, and manage tasks for seamless business operations.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />'
                    ],
                    [
                        'title' => 'Sales Opportunities',
                        'description' => 'Visualize and manage your sales pipeline with custom stages, lifecycle tracking, and detailed outcome analysis to drive your sales process forward.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />'
                    ],
                    [
                        'title' => 'Notes & Organization',
                        'description' => 'Capture and categorize notes linked to people, companies, and tasks. Share updates with your team and quickly retrieve important information.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />'
                    ],
                    [
                        'title' => 'Customizable Data Model',
                        'description' => 'Tailor the CRM to your business needs with user and system-defined custom fields, dynamic forms, and validation rules for data integrity.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />'
                    ],
                ];
            @endphp

            @foreach ($features as $feature)
                <div
                    class="group relative bg-white dark:bg-gray-800 rounded-3xl shadow-lg hover:shadow-xl dark:shadow-gray-900/30 dark:hover:shadow-gray-900/40 p-8 transition-all duration-300 hover:-translate-y-1 hover:scale-[1.02] flex flex-col h-full border border-gray-100 dark:border-gray-700/50 backdrop-blur-sm">
                    <!-- Subtle gradient overlay for hover effect -->
                    <div
                        class="absolute inset-0 rounded-3xl bg-gradient-to-br from-primary/[0.03] to-transparent dark:from-primary/[0.07] dark:to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>

                    <!-- Feature icon with modern styling -->
                    <div
                        class="absolute right-6 top-6 w-14 h-14 bg-primary/10 dark:bg-primary/20 rounded-2xl flex items-center justify-center text-primary dark:text-primary-400 group-hover:bg-primary dark:group-hover:bg-primary-700 group-hover:text-white dark:group-hover:text-white transition-colors duration-300 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor" aria-hidden="true">
                            {!! $feature['icon'] !!}
                        </svg>
                    </div>

                    <!-- Feature content -->
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3 group-hover:text-primary dark:group-hover:text-primary-400 transition-colors duration-300">
                        {{ $feature['title'] }}
                    </h3>
                    <p class="text-gray-600 dark:text-gray-300 flex-grow mt-4 leading-relaxed">
                        {{ $feature['description'] }}
                    </p>
                </div>
            @endforeach
        </div>

        <!-- Call-to-action banner -->
        <div class="mt-20 relative overflow-hidden">
            <div
                class="bg-gradient-to-r from-primary/90 to-indigo-600/90 dark:from-primary-800/90 dark:to-indigo-800/90 rounded-3xl p-10 md:p-12 shadow-lg">
                <!-- Background design elements -->
                <div class="absolute inset-0 overflow-hidden rounded-3xl">
                    <div class="absolute -top-24 -right-24 w-64 h-64 bg-white opacity-10 rounded-full blur-3xl"></div>
                    <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-white opacity-10 rounded-full blur-3xl"></div>
                    <div
                        class="absolute top-0 left-0 w-full h-full bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjIiIGZpbGw9ImN1cnJlbnRDb2xvciIvPjwvc3ZnPg==')] bg-[length:60px_60px] text-white opacity-[0.07]"></div>
                </div>

                <!-- Content -->
                <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-10">
                    <div class="max-w-2xl">
                        <h3 class="text-3xl font-bold text-white mb-4">Ready to transform your customer
                            relationships?</h3>
                        <p class="text-white/80 text-lg">Experience the power of Relaticle CRM today and take your
                            business to the next level.</p>
                    </div>

                    <a href="{{ route('register') }}"
                       class="flex-shrink-0 inline-flex items-center gap-2 bg-white dark:bg-gray-900 text-primary dark:text-primary-400 px-8 py-4 rounded-full text-lg font-medium shadow-lg shadow-black/10 hover:shadow-xl hover:-translate-y-0.5 transition-all duration-300">
                        Get Started Free
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

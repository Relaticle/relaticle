<!-- Modern Features Section -->
<section id="features" class="py-24 bg-gradient-to-b from-white to-slate-50 relative overflow-hidden">
    <!-- Decorative elements -->
    <div class="absolute inset-0 overflow-hidden">
        <div
            class="absolute top-1/2 right-0 w-96 h-96 bg-blue-100 opacity-40 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>
        <div class="absolute bottom-0 left-1/4 w-64 h-64 bg-indigo-100 opacity-30 rounded-full blur-3xl"></div>
    </div>

    <div class="container max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
        <!-- Section Header -->
        <div class="max-w-3xl mx-auto text-center mb-16">
            <span
                class="inline-block px-3 py-1 text-sm font-medium text-indigo-600 bg-indigo-100 rounded-full mb-3">
                Powerful Features
            </span>
            <h2 class="text-4xl font-bold text-gray-900 md:text-5xl">
                Everything you need to <span
                    class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-indigo-600">manage relationships</span>
            </h2>
            <p class="mt-4 text-xl text-gray-600">
                A comprehensive suite of tools to streamline your client management workflow
            </p>
        </div>

        <!-- Features Grid -->
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
                    class="group relative bg-white rounded-2xl shadow-lg hover:shadow-xl p-8 transition-all duration-300 hover:-translate-y-1 flex flex-col h-full border border-gray-100">
                    <div
                        class="absolute right-6 top-6 w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-colors duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor">
                            {!! $feature['icon'] !!}
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-primary transition-colors duration-300">{{ $feature['title'] }}</h3>
                    <p class="text-gray-600 flex-grow mt-3">{{ $feature['description'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section> 
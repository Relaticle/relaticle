<x-guest-layout>
    <!-- Hero Section -->
    <section class="bg-white">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <h1 class="text-6xl leading-20 font-bold text-center text-primary">
                The Next-Generation <br/> Open-Source CRM Platform
            </h1>
            <p class="text-center text-2xl text-[#6D6E71] mt-3">
                Transforming Client Relationship Management with Innovation and Efficiency
            </p>
            <div class="mt-8 flex justify-center items-center flex-col space-y-4">
                <a href="{{ route('register') }}"
                   class="bg-primary z-20 text-white px-8 py-4 rounded-md text-lg font-medium hover:bg-opacity-90 transition">
                    Get Started
                </a>
                <a href="https://github.com/relaticle/relaticle" target="_blank">
                    <img src="https://img.shields.io/github/stars/relaticle/relaticle" alt="stars">
                </a>
            </div>

            <!-- App Preview Image with Animation -->
            <div class="mt-12 flex justify-center">
                <img src="{{ asset('images/app-preview.png') }}" alt="App Preview"
                     class="w-full border-2 shadow-2xl rounded max-w-3xl">
            </div>
        </div>
    </section>


    <!-- Features Section -->
    <section class="container mx-auto px-6 py-12">
        <h2 class="text-4xl font-bold text-center primary-color mb-12">Features</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

            <!-- Task Management -->
            <div
                class="card bg-white rounded-lg shadow-md p-6 hover:shadow-lg transform transition-transform hover:scale-105">
                <div class="flex items-center mb-4">
                    <span class="emoji primary-color mr-4">🗂️</span>
                    <h3 class="text-2xl font-bold primary-color">Task Management</h3>
                </div>
                <p class="text-gray-700">Stay on top of your team's productivity with easy task creation, assignment,
                    and tracking. Receive real-time notifications to keep everyone updated.</p>
            </div>

            <!-- People Management -->
            <div
                class="card bg-white rounded-lg shadow-md p-6 hover:shadow-lg transform transition-transform hover:scale-105">
                <div class="flex items-center mb-4">
                    <span class="emoji primary-color mr-4">👥</span>
                    <h3 class="text-2xl font-bold primary-color">People Management</h3>
                </div>
                <p class="text-gray-700">Effortlessly manage individual contacts with detailed profiles, advanced search
                    options, and complete interaction histories to personalize your outreach.</p>
            </div>

            <!-- Company Management -->
            <div
                class="card bg-white rounded-lg shadow-md p-6 hover:shadow-lg transform transition-transform hover:scale-105">
                <div class="flex items-center mb-4">
                    <span class="emoji primary-color mr-4">🏢</span>
                    <h3 class="text-2xl font-bold primary-color">Company Management</h3>
                </div>
                <p class="text-gray-700">Maintain detailed company profiles and link them to individual contacts, track
                    opportunities, and manage tasks for seamless business operations.</p>
            </div>

            <!-- Sales Opportunities -->
            <div
                class="card bg-white rounded-lg shadow-md p-6 hover:shadow-lg transform transition-transform hover:scale-105">
                <div class="flex items-center mb-4">
                    <span class="emoji primary-color mr-4">📈</span>
                    <h3 class="text-2xl font-bold primary-color">Sales Opportunities</h3>
                </div>
                <p class="text-gray-700">Visualize and manage your sales pipeline with custom stages, lifecycle
                    tracking, and detailed outcome analysis to drive your sales process forward.</p>
            </div>

            <!-- Notes & Organization -->
            <div
                class="card bg-white rounded-lg shadow-md p-6 hover:shadow-lg transform transition-transform hover:scale-105">
                <div class="flex items-center mb-4">
                    <span class="emoji primary-color mr-4">📝</span>
                    <h3 class="text-2xl font-bold primary-color">Notes & Organization</h3>
                </div>
                <p class="text-gray-700">Capture and categorize notes linked to people, companies, and tasks. Share
                    updates with your team and quickly retrieve important information.</p>
            </div>

            <!-- Customizable Data Model -->
            <div
                class="card bg-white rounded-lg shadow-md p-6 hover:shadow-lg transform transition-transform hover:scale-105">
                <div class="flex items-center mb-4">
                    <span class="emoji primary-color mr-4">⚙️</span>
                    <h3 class="text-2xl font-bold primary-color">Customizable Data Model</h3>
                </div>
                <p class="text-gray-700">Tailor the CRM to your business needs with user and system-defined custom
                    fields, dynamic forms, and validation rules for data integrity.</p>
            </div>

        </div>
    </section>

    <!-- Community & Support -->
    <section class="container mx-auto mb-8 px-6 py-12 bg-gray-50 shadow rounded-md">
        <h3 class="text-3xl font-bold text-center primary-color mb-12">Join Our Community</h3>
        <p class="text-center text-gray-700 mb-8">
            As an open-source platform written with Laravel and Filament, Relaticle thrives on community collaboration.
            <br />
            Join our community to get help, share tips, and contribute to the project.
        </p>
        <div class="flex justify-center">
            <a href="https://github.com/relaticle/relaticle" target="_blank"
               class="bg-primary text-white px-6 py-3 rounded-lg font-semibold hover:bg-opacity-90 transition">
                Join the Community
            </a>
        </div>
    </section>

</x-guest-layout>

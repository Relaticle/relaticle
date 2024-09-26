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
</x-guest-layout>

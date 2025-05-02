<x-documentation::layout :title="config('app.name') . ' - ' . __('Documentation')" class="documentation-hub bg-gray-50">
    <div class="container px-4 py-12 mx-auto">
        <header class="mb-12 text-center">
            <h1 class="text-4xl font-bold mb-4 text-primary-600">
                Relaticle Documentation
            </h1>

            <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                Welcome to the Relaticle documentation hub. Here you'll find guides and resources to help you get the
                most out of Relaticle CRM.
            </p>
        </header>

        <section class="mb-12">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <x-documentation::card
                    title="Business Guide"
                    icon="<path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' />"
                    url="/documentation/business"
                    class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 border border-gray-100"
                >
                    <p class="text-gray-600">
                        The Business Guide provides an overview of Relaticle from a business perspective. It explains
                        how Relaticle can benefit your organization and how to leverage its features effectively.
                    </p>
                </x-documentation::card>

                <x-documentation::card
                    title="Quick Start Guide"
                    icon="<path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 6v6m0 0v6m0-6h6m-6 0H6' />"
                    url="/documentation/quickstart"
                    class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 border border-gray-100"
                >
                    <p class="text-gray-600">
                        The Quick Start Guide offers step-by-step instructions for new users. It walks you through
                        setting up your account, creating your first records, and establishing an effective workflow.
                        Perfect for users who want to get up and running quickly.
                    </p>
                </x-documentation::card>

                <x-documentation::card
                    title="Technical Guide"
                    icon="<path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4' />"
                    url="/documentation/technical"
                    class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 border border-gray-100"
                >
                    <p class="text-gray-600">
                        The Technical Guide details the system architecture and development guidelines. It covers the
                        tech stack, core components, relationships, development standards, and more. This guide is
                        intended for developers and technical administrators.
                    </p>
                </x-documentation::card>

                <x-documentation::card
                    title="API Documentation"
                    icon="<path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' />"
                    url="/documentation/api"
                    class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 border border-gray-100"
                >
                    <p class="text-gray-600">
                        The API Documentation provides information for developers who want to integrate with the
                        Relaticle API. It includes authentication methods, endpoint details, and best practices for API
                        usage.
                    </p>
                </x-documentation::card>
            </div>
        </section>
    </div>
</x-documentation::layout>

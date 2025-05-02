<x-documentation::layout title="Search Results">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Search Results for "{{ $query }}"
            </h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Found {{ $results->count() }} {{ Str::plural('result', $results->count()) }}
            </p>
        </div>

        <div class="mb-6">
            <x-documentation::search-form :query="$query" />
        </div>

        @if($results->isEmpty())
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 text-center">
                <p class="text-gray-600 dark:text-gray-400">No results found for "{{ $query }}"</p>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-500">Try using different keywords or check spelling</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($results as $result)
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 hover:shadow-md transition-shadow">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                            <a href="{{ $result->url }}" class="hover:underline">{{ $result->title }}</a>
                        </h2>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">
                            {!! $result->excerpt !!}
                        </p>
                        <div class="mt-4 flex items-center text-sm text-gray-500 dark:text-gray-500">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                {{ $documentTypes[$result->type]['title'] }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-documentation::layout> 
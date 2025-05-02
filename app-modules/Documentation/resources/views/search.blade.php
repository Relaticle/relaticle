<x-documentation::layout title="Search Results">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8 border-b border-gray-200 dark:border-gray-700 pb-5">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Search Results for "<span class="text-primary-600 dark:text-primary-400">{{ $query }}</span>"
            </h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Found {{ $results->count() }} {{ Str::plural('result', $results->count()) }}
            </p>
        </div>

        <div class="mb-8">
            <x-documentation::search-form :query="$query" />
        </div>

        @if($results->isEmpty())
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-8 text-center max-w-2xl mx-auto">
                <div class="flex justify-center mb-4">
                    <svg class="h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">No results found</h3>
                <p class="mt-2 text-gray-600 dark:text-gray-400">No matches found for "<span class="font-medium">{{ $query }}</span>"</p>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-500">Try using different keywords or check spelling</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($results as $result)
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden hover:shadow-md transition-shadow border border-gray-100 dark:border-gray-700">
                        <div class="p-5">
                            <div class="mb-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200">
                                    {{ $documentTypes[$result->type]['title'] }}
                                </span>
                            </div>
                            
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white line-clamp-2">
                                <a href="{{ $result->url }}" class="hover:text-primary-600 dark:hover:text-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-500">
                                    {{ $result->title }}
                                </a>
                            </h2>
                            
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 line-clamp-3">
                                {!! $result->excerpt !!}
                            </p>
                            
                            <div class="mt-4 flex justify-end">
                                <a href="{{ $result->url }}" class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 flex items-center">
                                    View document
                                    <svg class="ml-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-documentation::layout> 
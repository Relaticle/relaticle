@props(['query' => ''])

<form action="{{ route('documentation.search') }}" method="GET"
      class="relative" {{ $attributes->merge(['class' => 'relative']) }}>
    <div>
        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
            <x-heroicon-o-magnifying-glass class="h-5 w-5 text-gray-400"/>
        </div>
        <input
            type="text"
            name="query"
            value="{{ $query }}"
            class="block w-full pl-10 pr-12 py-3 border border-gray-200 dark:border-gray-800 rounded-md shadow-sm focus:outline-none focus:border-primary dark:bg-gray-900 dark:text-white transition-colors duration-200"
            placeholder="Search documentation..."
        >
        <button type="submit"
                class="absolute inset-y-0 right-0 flex items-center px-4 text-white bg-primary hover:bg-primary-600 rounded-r-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:ring-offset-gray-900">
            <span>Search</span>
        </button>
    </div>
</form>

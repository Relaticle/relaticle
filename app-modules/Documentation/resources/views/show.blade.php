<x-documentation::layout :title="config('app.name') . ' - ' . __('Documentation')">
    <div class="grid grid-cols-12 gap-6 lg:gap-8">
        <!-- Left Sidebar: Documentation Types - Hidden on mobile -->
        <div
            class="docs-sidebar hidden sm:block col-span-12 sm:col-span-3 lg:col-span-2 transition-opacity duration-300">
            <div class="sticky top-24 pr-4">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center space-x-2">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="h-4 w-4 text-documentation-600 dark:text-documentation-400"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <span>Documentation</span>
                </h2>
                <div class="flex flex-col space-y-1.5 border-l border-gray-200 dark:border-gray-800">
                    @foreach($documentTypes as $typeKey => $typeValue)
                        <a href="{{ route('documentation.show', $typeKey) }}"
                           class="pl-4 py-2 text-sm rounded-r-md flex items-center gap-2 transition-all
                                      {{ $currentType === $typeKey
                                        ? 'border-l-2 border-l-documentation-500 -ml-[1px] pl-[17px] dark:border-l-documentation-400 bg-documentation-50/50 dark:bg-documentation-900/10 text-documentation-600 dark:text-documentation-400 font-medium'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:border-l hover:border-l-gray-300 dark:hover:border-l-gray-700 hover:-ml-[1px] hover:pl-[17px]' }}">
                            <span>{{ $typeValue['title'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Main Content Column -->
        <div
            class="docs-content-wrapper col-span-12 sm:col-span-9 md:col-span-6 lg:col-span-7 transition-opacity duration-300">
            <!-- Documentation content with improved typography -->
            <div id="documentation-content"
            >
                {!! $content !!}
            </div>
        </div>

        <!-- Right Sidebar: Table of Contents -->
        <aside class="hidden lg:block w-full pb-16 col-span-2 print-hidden">
            <div class="sticky top-[1rem]">
                @if(count($tableOfContents))
                    <h3 class="text-base font-bold mb-2">
                        On this page
                    </h3>
                    <ul class="grid gap-2 mb-10">
                        @foreach($tableOfContents as $fragment => $title)
                            <li class="text-sm">
                                <a href="#{{ $fragment }}" class="docs-submenu-item">
                                    {{ $title }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </aside>
    </div>
</x-documentation::layout>

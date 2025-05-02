@props(['content', 'documentTypes', 'currentType'])

<div class="py-8 md:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Mobile Controls -->
        <div class="flex flex-col space-y-4 sm:hidden mb-6">
            <!-- Mobile Doc Types Toggle -->
            <button id="mobile-doc-types-toggle"
                    x-data="{ open: false }"
                    @click="open = !open"
                    class="w-full flex justify-between items-center px-4 py-3.5 text-left text-sm font-medium text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-900 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors focus:outline-none focus:ring-2 focus:ring-documentation-500/40">
                <div class="flex items-center space-x-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-documentation-600 dark:text-documentation-400"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <span>Documentation Types</span>
                </div>
                <svg x-bind:class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg"
                     class="h-4 w-4 transition-transform duration-200" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <!-- Mobile Doc Types Container (populated via Alpine.js) -->
            <div id="mobile-doc-types-container"
                 x-data="{ open: false }"
                 x-show="open"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-y-95"
                 x-transition:enter-end="opacity-100 scale-y-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-y-100"
                 x-transition:leave-end="opacity-0 scale-y-95"
                 class="bg-white dark:bg-gray-900 rounded-lg shadow-md border border-gray-200 dark:border-gray-800 overflow-hidden">
                <div class="p-3 flex flex-col space-y-1.5">
                    <!-- Document types will be displayed here -->
                    @foreach($documentTypes as $typeKey => $typeValue)
                        <a href="{{ route('documentation.show', $typeKey) }}"
                           class="px-4 py-2.5 text-sm rounded-md flex items-center gap-2 transition-colors
                                  {{ $currentType === $typeKey
                                    ? 'bg-documentation-50 dark:bg-documentation-900/20 text-documentation-600 dark:text-documentation-400 font-medium'
                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/70' }}">
                            <span>{{ $typeValue['title'] }}</span>
                            @if($currentType === $typeKey)
                                <span class="ml-auto text-xs bg-documentation-100 dark:bg-documentation-900/40 text-documentation-600 dark:text-documentation-400 px-2 py-0.5 rounded-full">Active</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- Mobile TOC Toggle -->
            <button id="mobile-toc-toggle"
                    x-data="{ open: false }"
                    @click="open = !open"
                    class="w-full flex justify-between items-center px-4 py-3.5 text-left text-sm font-medium text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-900 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors focus:outline-none focus:ring-2 focus:ring-documentation-500/40">
                <div class="flex items-center space-x-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-documentation-600 dark:text-documentation-400"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M4 6h16M4 12h16M4 18h7"/>
                    </svg>
                    <span>On this page</span>
                </div>
                <svg x-bind:class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg"
                     class="h-4 w-4 transition-transform duration-200" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <!-- Mobile TOC Container (populated via Alpine.js) -->
            <div id="mobile-toc-container"
                 x-data="{ open: false }"
                 x-show="open"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-y-95"
                 x-transition:enter-end="opacity-100 scale-y-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-y-100"
                 x-transition:leave-end="opacity-0 scale-y-95"
                 class="bg-white dark:bg-gray-900 rounded-lg shadow-md border border-gray-200 dark:border-gray-800 overflow-hidden max-h-[50vh] overflow-y-auto docs-scrollbar">
                <div class="p-3">
                    <!-- TOC will be populated here via Alpine.js -->
                </div>
            </div>
        </div>

        <!-- Three Column Layout -->
        <div class="grid grid-cols-12 gap-6 lg:gap-8">
            <!-- Left Sidebar: Documentation Types - Hidden on mobile -->
            <div class="docs-sidebar hidden sm:block col-span-12 sm:col-span-3 lg:col-span-2 transition-opacity duration-300">
                <div class="sticky top-24 pr-4">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center space-x-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-documentation-600 dark:text-documentation-400"
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
            <div class="docs-content-wrapper col-span-12 sm:col-span-9 md:col-span-6 lg:col-span-7 transition-opacity duration-300">
                <!-- Documentation content with improved typography -->
                <div id="documentation-content" class="docs-content prose prose-sm sm:prose max-w-none dark:prose-invert
                    prose-headings:font-medium prose-headings:text-gray-900 dark:prose-headings:text-white prose-headings:scroll-mt-24
                    prose-h1:text-3xl prose-h1:font-bold prose-h1:mb-6
                    prose-h2:text-2xl prose-h2:mt-12 prose-h2:mb-4
                    prose-h3:text-xl prose-h3:mt-8 prose-h3:mb-4
                    prose-p:text-gray-800 dark:prose-p:text-gray-200
                    prose-a:text-documentation-600 dark:prose-a:text-documentation-400 prose-a:font-medium prose-a:no-underline hover:prose-a:underline
                    prose-img:rounded-md prose-img:shadow-md
                    prose-strong:text-gray-900 dark:prose-strong:text-white prose-strong:font-semibold
                    prose-code:text-documentation-600 dark:prose-code:text-documentation-400 prose-code:bg-gray-100 dark:prose-code:bg-gray-900 prose-code:px-1 prose-code:py-0.5 prose-code:rounded-md prose-code:before:content-[''] prose-code:after:content-['']
                    prose-pre:bg-gray-900 dark:prose-pre:bg-black prose-pre:rounded-lg prose-pre:border prose-pre:border-gray-200 dark:prose-pre:border-gray-800 prose-pre:shadow-sm
                    prose-pre:overflow-x-auto
                    prose-ul:pl-5 prose-ol:pl-5
                    prose-li:text-gray-800 dark:prose-li:text-gray-200 prose-li:my-2
                    prose-table:border prose-table:border-gray-200 dark:prose-table:border-gray-800
                    prose-th:bg-gray-100 dark:prose-th:bg-gray-900 prose-th:p-2 prose-th:text-left
                    prose-td:p-2 prose-td:border-t prose-td:border-gray-200 dark:prose-td:border-gray-800
                    prose-blockquote:border-l-4 prose-blockquote:border-documentation-200 dark:prose-blockquote:border-documentation-900 prose-blockquote:bg-documentation-50/30 dark:prose-blockquote:bg-documentation-900/10 prose-blockquote:pl-4 prose-blockquote:py-1 prose-blockquote:text-gray-800 dark:prose-blockquote:text-gray-200">
                    {!! $content !!}
                </div>
            </div>

            <!-- Right Sidebar: Table of Contents -->
            <div class="docs-toc-container col-span-12 sm:col-span-3 hidden sm:block transition-opacity duration-300" x-data="docsToc">
                <div class="sticky top-24 border-gray-200 dark:border-gray-800 pl-6">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center space-x-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-documentation-600 dark:text-documentation-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h7" />
                        </svg>
                        <span>On this page</span>
                    </h2>
                    <div class="text-sm max-h-[calc(100vh-10rem)] overflow-y-auto pr-2 docs-scrollbar">
                        <div x-show="headings.length === 0" class="text-gray-500 dark:text-gray-400 italic">
                            No sections found
                        </div>
                        <ul x-show="headings.length > 0" class="space-y-1">
                            <template x-for="heading in headings" :key="heading.id">
                                <li>
                                    <a :href="`#${heading.id}`"
                                       @click.prevent="scrollToHeading(heading.id)"
                                       class="docs-toc-link"
                                       :class="{'active': activeHeading === heading.id}"
                                       :style="`padding-left: ${(heading.level - 1) * 0.75}rem`"
                                       x-text="heading.text">
                                    </a>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Code block component integration -->
<template x-data id="docs-code-block-template">
    <div x-data="docsCodeBlock" class="docs-code-block my-4">
        <div class="docs-code-block-header">
            <div class="docs-code-block-language" x-text="language || 'text'"></div>
            <button @click="copyCode" class="docs-code-block-copy" :aria-label="`Copy ${language} code`">
                <span x-show="!copied">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </span>
                <span x-show="copied">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7" />
                    </svg>
                </span>
            </button>
        </div>
        <div class="p-4 overflow-x-auto docs-scrollbar">
            <slot></slot>
        </div>
    </div>
</template> 
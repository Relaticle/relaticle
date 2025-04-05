<x-guest-layout>
    <div class="py-8 md:py-12 bg-white dark:bg-black min-h-[calc(100vh-5rem)]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Improved Mobile Controls - Only visible on mobile -->
            <div class="flex flex-col space-y-4 sm:hidden mb-6">
                <!-- Mobile Doc Type Navigation Toggle -->
                <button id="doc-types-toggle" class="w-full flex justify-between items-center px-4 py-3 text-left text-sm font-medium text-black dark:text-white bg-gray-100 dark:bg-gray-900 rounded-lg">
                    <span>Documentation Types</span>
                    <svg id="doc-types-toggle-icon" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                
                <!-- Mobile Doc Types Container (initially hidden) -->
                <div id="doc-types-container" class="hidden bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
                    <div class="p-2 flex flex-col space-y-1">
                        @foreach($documentTypes as $typeKey => $typeValue)
                            <a href="{{ route('documentation.show', $typeKey) }}"
                               class="px-4 py-2 text-sm rounded-md flex items-center gap-1.5 
                                      {{ $currentType === $typeKey
                                        ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 font-medium'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/70' }}">
                                @if(isset($typeValue['icon']))
                                    <i class="{{ $typeValue['icon'] }} opacity-80"></i>
                                @endif
                                <span>{{ $typeValue['title'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
                
                <!-- Mobile TOC Toggle Button -->
                <button id="toc-toggle" class="w-full flex justify-between items-center px-4 py-3 text-left text-sm font-medium text-black dark:text-white bg-gray-100 dark:bg-gray-900 rounded-lg">
                    <span>On this page</span>
                    <svg id="toc-toggle-icon" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>

            <!-- Three Column Layout Grid -->
            <div class="grid grid-cols-12 gap-6 lg:gap-8">
                <!-- Left Sidebar: Documentation Types - Hidden on mobile -->
                <div class="hidden sm:block col-span-12 sm:col-span-3 lg:col-span-2">
                    <div class="sticky top-24 pr-4">
                        <h2 class="text-sm font-semibold text-black dark:text-white mb-4">
                            Documentation
                        </h2>
                        <div class="flex flex-col space-y-1">
                            @foreach($documentTypes as $typeKey => $typeValue)
                                <a href="{{ route('documentation.show', $typeKey) }}"
                                   class="px-3 py-2 text-sm rounded-md flex items-center gap-1.5 
                                          {{ $currentType === $typeKey
                                            ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 font-medium'
                                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/70' }}">
                                    @if(isset($typeValue['icon']))
                                        <i class="{{ $typeValue['icon'] }} opacity-80"></i>
                                    @endif
                                    <span>{{ $typeValue['title'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
                
                <!-- Main Content (Middle) -->
                <div class="col-span-12 sm:col-span-9 md:col-span-6 lg:col-span-7">
                    <!-- Main documentation content with Tailwind-style typography -->
                    <div id="documentation-content" class="prose prose-sm sm:prose max-w-none dark:prose-invert
                        prose-headings:font-medium prose-headings:text-black dark:prose-headings:text-white prose-headings:scroll-mt-24
                        prose-h1:text-3xl prose-h1:font-bold prose-h1:mb-6
                        prose-h2:text-2xl prose-h2:mt-12 prose-h2:mb-4
                        prose-h3:text-xl prose-h3:mt-8 prose-h3:mb-4
                        prose-p:text-gray-800 dark:prose-p:text-gray-200
                        prose-a:text-primary-600 dark:prose-a:text-primary-400 prose-a:font-medium prose-a:no-underline hover:prose-a:underline
                        prose-img:rounded-md prose-img:shadow-md
                        prose-strong:text-black dark:prose-strong:text-white prose-strong:font-semibold
                        prose-code:text-primary-600 dark:prose-code:text-primary-400 prose-code:bg-gray-100 dark:prose-code:bg-gray-900 prose-code:px-1 prose-code:py-0.5 prose-code:rounded-md prose-code:before:content-[''] prose-code:after:content-['']
                        prose-pre:bg-gray-900 dark:prose-pre:bg-black prose-pre:rounded-lg prose-pre:border prose-pre:border-gray-200 dark:prose-pre:border-gray-800 prose-pre:shadow-sm
                        prose-pre:overflow-x-auto prose-pre:custom-scrollbar
                        prose-ul:pl-5 prose-ol:pl-5
                        prose-li:text-gray-800 dark:prose-li:text-gray-200 prose-li:my-2
                        prose-table:border prose-table:border-gray-200 dark:prose-table:border-gray-800
                        prose-th:bg-gray-100 dark:prose-th:bg-gray-900 prose-th:p-2 prose-th:text-left
                        prose-td:p-2 prose-td:border-t prose-td:border-gray-200 dark:prose-td:border-gray-800
                        prose-blockquote:border-l-4 prose-blockquote:border-gray-300 dark:prose-blockquote:border-gray-700 prose-blockquote:bg-gray-50 dark:prose-blockquote:bg-gray-900 prose-blockquote:pl-4 prose-blockquote:py-1 prose-blockquote:text-gray-800 dark:prose-blockquote:text-gray-200">
                        {!! $documentContent !!}
                    </div>
                </div>
                
                <!-- Right Sidebar: TOC Container -->
                <div id="toc-container" class="col-span-12 sm:col-span-3 hidden sm:block">
                    <div class="sticky top-24 border-l border-gray-200 dark:border-gray-800 pl-6">
                        <h2 class="text-sm font-semibold text-black dark:text-white mb-4">
                            On this page
                        </h2>
                        <div id="toc" class="text-sm max-h-[calc(100vh-10rem)] overflow-y-auto pr-2 custom-scrollbar">
                            <!-- Table of contents will be generated by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Tailwind-style custom scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 3px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 3px;
        }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #4b5563;
        }
        
        /* Hide scrollbar for Chrome, Safari and Opera */
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
        
        /* Hide scrollbar for IE, Edge and Firefox */
        .scrollbar-hide {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }
        
        /* Clean code blocks */
        #documentation-content pre {
            background-color: #f8fafc;
            border-color: #e2e8f0;
        }
        
        .dark #documentation-content pre {
            background-color: #0f172a;
            border-color: #1e293b;
        }

        /* Tailwind-style link focus */
        #documentation-content a:focus {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 640px) {
            #toc-container.sm\:block:not(.hidden) {
                margin-top: 2rem;
                padding-top: 2rem;
                border-top: 1px solid #e5e7eb;
                border-left: none;
                padding-left: 0;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Find all headings in the documentation content
            const content = document.getElementById('documentation-content');
            const headings = content.querySelectorAll('h1, h2, h3');
            const toc = document.getElementById('toc');
            const tocContainer = document.getElementById('toc-container');
            const tocToggle = document.getElementById('toc-toggle');
            const tocToggleIcon = document.getElementById('toc-toggle-icon');
            const docTypesToggle = document.getElementById('doc-types-toggle');
            const docTypesToggleIcon = document.getElementById('doc-types-toggle-icon');
            const docTypesContainer = document.getElementById('doc-types-container');

            // Mobile TOC toggle functionality
            if (tocToggle) {
                tocToggle.addEventListener('click', function() {
                    tocContainer.classList.toggle('hidden');
                    if (tocContainer.classList.contains('hidden')) {
                        tocToggleIcon.style.transform = 'rotate(0deg)';
                    } else {
                        tocToggleIcon.style.transform = 'rotate(180deg)';
                    }
                });
            }
            
            // Mobile Doc Types toggle functionality
            if (docTypesToggle) {
                docTypesToggle.addEventListener('click', function() {
                    docTypesContainer.classList.toggle('hidden');
                    if (docTypesContainer.classList.contains('hidden')) {
                        docTypesToggleIcon.style.transform = 'rotate(0deg)';
                    } else {
                        docTypesToggleIcon.style.transform = 'rotate(180deg)';
                    }
                });
            }

            // Handle window resize for visibility
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 640) {
                    tocContainer.classList.remove('hidden');
                    if (docTypesContainer) {
                        docTypesContainer.classList.add('hidden');
                    }
                } else {
                    tocContainer.classList.add('hidden');
                    if (tocToggle) {
                        tocToggleIcon.style.transform = 'rotate(0deg)';
                    }
                }
            });

            // Clear any existing TOC content
            toc.innerHTML = '';

            // Generate table of contents with Tailwind-style
            headings.forEach((heading, index) => {
                // Create a unique ID for each heading if it doesn't have one
                if (!heading.id) {
                    heading.id = 'heading-' + index;
                }

                const tagName = heading.tagName.toLowerCase();
                const level = parseInt(tagName.charAt(1)) - 1; // h1 = 0, h2 = 1, etc.

                // Create TOC entry
                const tocEntry = document.createElement('a');
                tocEntry.href = '#' + heading.id;
                tocEntry.textContent = heading.textContent;
                
                // Base classes for all TOC entries
                tocEntry.classList.add(
                    'block',
                    'mb-2',
                    'hover:text-primary-600',
                    'dark:hover:text-primary-400',
                    'transition-colors',
                    'truncate'
                );

                // Add styling based on heading level
                if (level === 0) {
                    tocEntry.classList.add('text-black', 'dark:text-white', 'font-medium');
                    tocEntry.style.paddingLeft = '0';
                } else if (level === 1) {
                    tocEntry.classList.add('text-gray-900', 'dark:text-gray-100');
                    tocEntry.style.paddingLeft = '0.75rem';
                } else {
                    tocEntry.classList.add('text-gray-600', 'dark:text-gray-400');
                    tocEntry.style.paddingLeft = '1.5rem';
                }

                // Smooth scrolling
                tocEntry.addEventListener('click', function (e) {
                    e.preventDefault();
                    const headerOffset = 80;
                    const elementPosition = document.querySelector(this.getAttribute('href')).getBoundingClientRect().top;
                    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });

                    // Hide TOC on mobile after clicking
                    if (window.innerWidth < 640) {
                        tocContainer.classList.add('hidden');
                        tocToggleIcon.style.transform = 'rotate(0deg)';
                        tocToggle.setAttribute('data-open', 'false');
                    }
                });

                toc.appendChild(tocEntry);
            });

            // Active section highlighting
            function highlightActiveTocItem() {
                // Get all section headers
                const headingElements = Array.from(headings);
                if (headingElements.length === 0) return;

                // Determine which section is in view
                let activeHeading = headingElements[0];
                const headerOffset = 100;

                headingElements.forEach((heading) => {
                    const rect = heading.getBoundingClientRect();
                    if (rect.top <= headerOffset + 50) {
                        activeHeading = heading;
                    }
                });

                // Remove active class from all TOC items
                const tocItems = toc.querySelectorAll('a');
                tocItems.forEach(item => {
                    item.classList.remove('text-primary-600', 'dark:text-primary-400', 'font-medium');
                });

                // Add active class to current TOC item
                const currentTocItem = toc.querySelector(`a[href="#${activeHeading.id}"]`);
                if (currentTocItem) {
                    currentTocItem.classList.add('text-primary-600', 'dark:text-primary-400', 'font-medium');
                }
            }

            // Highlight active section on scroll
            let isScrolling = false;
            window.addEventListener('scroll', function() {
                if (!isScrolling) {
                    window.requestAnimationFrame(function() {
                        highlightActiveTocItem();
                        isScrolling = false;
                    });
                    isScrolling = true;
                }
            });
            
            // Initialize active section
            highlightActiveTocItem();
            
            // Add copy button to code blocks
            document.querySelectorAll('pre').forEach(block => {
                // Create wrapper for positioning
                const wrapper = document.createElement('div');
                wrapper.className = 'relative group';
                block.parentNode.insertBefore(wrapper, block);
                wrapper.appendChild(block);
                
                // Create copy button
                const button = document.createElement('button');
                button.className = 'absolute top-3 right-3 bg-white dark:bg-gray-800 text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white p-1.5 rounded-md border border-gray-200 dark:border-gray-700 opacity-0 group-hover:opacity-100 transition-opacity';
                button.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" />
                        <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z" />
                    </svg>
                `;
                
                button.addEventListener('click', () => {
                    const code = block.querySelector('code').textContent;
                    navigator.clipboard.writeText(code);
                    
                    // Show copied indicator
                    button.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    `;
                    
                    setTimeout(() => {
                        button.innerHTML = `
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" />
                                <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z" />
                            </svg>
                        `;
                    }, 1000);
                });
                
                wrapper.appendChild(button);
            });
        });
    </script>
</x-guest-layout>



<x-guest-layout :title="($documentTitle ?? 'Documentation') . ' - ' . config('app.name')">
    <!-- Add this script to the very top of the document to apply loading state quickly -->
    <script>
        // Set loading state on initial page load
        document.documentElement.classList.add('is-loading');
    </script>
    <div class="py-8 md:py-12 bg-white dark:bg-black min-h-[calc(100vh-5rem)]">
        <!-- Page Transition Overlay -->
        <div id="page-transition-overlay" class="fixed inset-0 bg-white dark:bg-black z-50 pointer-events-none transition-opacity duration-300"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Page Header with Animation -->
            <div class="mb-8 animate-fade-in">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ $documentTitle ?? 'Documentation' }}</h1>
                <p class="text-gray-600 dark:text-gray-400">Learn how to use Relaticle and maximize your experience.</p>
            </div>

            <!-- Improved Mobile Controls with Better Styling -->
            <div class="flex flex-col space-y-4 sm:hidden mb-6">
                <!-- Mobile Doc Type Navigation Toggle - Enhanced Design -->
                <button id="doc-types-toggle" class="w-full flex justify-between items-center px-4 py-3.5 text-left text-sm font-medium text-black dark:text-white bg-gray-100 dark:bg-gray-900 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500/40">
                    <div class="flex items-center space-x-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <span>Documentation Types</span>
                    </div>
                    <svg id="doc-types-toggle-icon" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- Mobile Doc Types Container - Enhanced Styling -->
                <div id="doc-types-container" class="hidden bg-white dark:bg-gray-900 rounded-lg shadow-md border border-gray-200 dark:border-gray-800 overflow-hidden transform origin-top transition-all duration-200 scale-y-95 opacity-0">
                    <div class="p-3 flex flex-col space-y-1.5">
                        @foreach($documentTypes as $typeKey => $typeValue)
                            <a href="{{ route('documentation.show', $typeKey) }}"
                               class="px-4 py-2.5 text-sm rounded-md flex items-center gap-2 transition-colors
                                      {{ $currentType === $typeKey
                                        ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 font-medium'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/70' }}">
                                <span>{{ $typeValue['title'] }}</span>
                                @if($currentType === $typeKey)
                                    <span class="ml-auto text-xs bg-primary-100 dark:bg-primary-900/40 text-primary-600 dark:text-primary-400 px-2 py-0.5 rounded-full">Active</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>

                <!-- Mobile TOC Toggle Button - Enhanced Design -->
                <button id="toc-toggle" class="w-full flex justify-between items-center px-4 py-3.5 text-left text-sm font-medium text-black dark:text-white bg-gray-00 dark:bg-gray-900 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500/40">
                    <div class="flex items-center space-x-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h7" />
                        </svg>
                        <span>On this page</span>
                    </div>
                    <svg id="toc-toggle-icon" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>

            <!-- Three Column Layout Grid with Animation -->
            <div class="grid grid-cols-12 gap-6 lg:gap-8">
                <!-- Left Sidebar: Documentation Types - Hidden on mobile, Animated on desktop -->
                <div class="hidden sm:block col-span-12 sm:col-span-3 lg:col-span-2 animate-fade-in" style="animation-delay: 100ms;">
                    <div class="sticky top-24 pr-4">
                        <h2 class="text-sm font-semibold text-black dark:text-white mb-4 flex items-center space-x-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <span>Documentation</span>
                        </h2>
                        <div class="flex flex-col space-y-1.5 border-l border-gray-200 dark:border-gray-800">
                            @foreach($documentTypes as $typeKey => $typeValue)
                                <a href="{{ route('documentation.show', $typeKey) }}"
                                   class="pl-4 py-2 text-sm rounded-r-md flex items-center gap-2 transition-all
                                          {{ $currentType === $typeKey
                                            ? 'border-l-2 border-l-primary-500 -ml-[1px] pl-[17px] dark:border-l-primary-400 bg-primary-50/50 dark:bg-primary-900/10 text-primary-600 dark:text-primary-400 font-medium'
                                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:border-l hover:border-l-gray-300 dark:hover:border-l-gray-700 hover:-ml-[1px] hover:pl-[17px]' }}">

                                    <span>{{ $typeValue['title'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Main Content (Middle) - Enhanced with Animation -->
                <div class="col-span-12 sm:col-span-9 md:col-span-6 lg:col-span-7 animate-fade-in" style="animation-delay: 150ms;">
                    <!-- Content header with breadcrumbs -->
                    <div class="mb-6 flex flex-wrap items-center text-sm text-gray-500 dark:text-gray-400">
                        <a href="{{ route('documentation.index') }}" class="hover:text-primary dark:hover:text-primary-400 transition-colors">Docs</a>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mx-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        <span class="font-medium text-gray-800 dark:text-gray-200">{{ $documentTitle ?? 'Documentation' }}</span>
                    </div>

                    <!-- Main documentation content with improved typography -->
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
                        prose-blockquote:border-l-4 prose-blockquote:border-primary-200 dark:prose-blockquote:border-primary-900 prose-blockquote:bg-primary-50/30 dark:prose-blockquote:bg-primary-900/10 prose-blockquote:pl-4 prose-blockquote:py-1 prose-blockquote:text-gray-800 dark:prose-blockquote:text-gray-200">
                        {!! $documentContent !!}
                    </div>
                </div>

                <!-- Right Sidebar: TOC Container with Animation -->
                <div id="toc-container" class="col-span-12 sm:col-span-3 hidden sm:block animate-fade-in" style="animation-delay: 200ms;">
                    <div class="sticky top-24 border-gray-200 dark:border-gray-800 pl-6">
                        <h2 class="text-sm font-semibold text-black dark:text-white mb-4 flex items-center space-x-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h7" />
                            </svg>
                            <span>On this page</span>
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
        /* Animation Keyframes */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Utility Animation Classes */
        .animate-fade-in {
            opacity: 0;
            animation: fadeIn 0.3s ease-out forwards;
            animation-delay: 0ms;
        }

        /* Page Transition Overlay */
        #page-transition-overlay {
            opacity: 0;
        }

        html.is-loading #page-transition-overlay {
            opacity: 1;
        }

        /* Immediate render for critical content to prevent layout shift */
        .critical-content {
            animation: none !important;
            opacity: 1 !important;
        }

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

        /* Improved code blocks with syntax highlighting theme */
        #documentation-content pre {
            background-color: #f8fafc;
            border-color: #e2e8f0;
        }

        .dark #documentation-content pre {
            background-color: #0f172a;
            border-color: #1e293b;
        }

        /* Code block language tag */
        #documentation-content pre:before {
            content: attr(data-language);
            display: block;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 0.75rem;
            color: #64748b;
            padding: 0.5rem 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .dark #documentation-content pre:before {
            color: #94a3b8;
            border-bottom: 1px solid #1e293b;
        }

        /* Tailwind-style link focus */
        #documentation-content a:focus {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
        }

        /* Improved accessibility for keyboard users */
        a:focus-visible, button:focus-visible {
            outline: 2px solid #3b82f6 !important;
            outline-offset: 2px !important;
        }

        /* Active TOC item highlight */
        .toc-active {
            color: #7c3aed !important; /* primary-600 */
            font-weight: 500;
            border-left-color: #7c3aed !important;
        }

        .dark .toc-active {
            color: #a78bfa !important; /* primary-400 */
            border-left-color: #a78bfa !important;
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

            #doc-types-container.show {
                transform: scaleY(1);
                opacity: 1;
            }
        }

        /* Reduced motion preferences */
        @media (prefers-reduced-motion: reduce) {
            .animate-fade-in {
                animation: none !important;
                opacity: 1 !important;
                transform: translateY(0) !important;
            }

            * {
                transition-duration: 0.05s !important;
                animation-duration: 0.05s !important;
            }
        }
    </style>

    <script>
        // Page Transition Handling
        document.addEventListener('DOMContentLoaded', function() {
            // Remove loading state after content is fully loaded
            document.documentElement.classList.remove('is-loading');

            // Fade out the overlay
            const overlay = document.getElementById('page-transition-overlay');
            overlay.style.opacity = '0';

            // Make animations start after overlay fades
            setTimeout(() => {
                document.querySelectorAll('.animate-fade-in').forEach((el) => {
                    el.style.opacity = '0';
                    el.style.animation = 'fadeIn 0.3s ease-out forwards';
                });
            }, 50);
        });

        // Apply loading state immediately on navigation
        window.addEventListener('beforeunload', function() {
            document.documentElement.classList.add('is-loading');
        });

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

            // Add language indicators to code blocks
            document.querySelectorAll('pre code').forEach(block => {
                const classNames = block.className.split(' ');
                let language = 'code';

                for (const className of classNames) {
                    if (className.startsWith('language-')) {
                        language = className.replace('language-', '');
                        break;
                    }
                }

                // Set data attribute on parent pre element
                block.parentElement.setAttribute('data-language', language);
            });

            // Enhanced Mobile TOC toggle functionality
            if (tocToggle && tocContainer) {
                tocToggle.addEventListener('click', function() {
                    tocContainer.classList.toggle('hidden');
                    if (tocContainer.classList.contains('hidden')) {
                        tocToggleIcon.style.transform = 'rotate(0deg)';
                    } else {
                        tocToggleIcon.style.transform = 'rotate(180deg)';
                    }
                });
            }

            // Enhanced Mobile Doc Types toggle functionality
            if (docTypesToggle && docTypesContainer) {
                docTypesToggle.addEventListener('click', function() {
                    const isHidden = docTypesContainer.classList.contains('hidden');

                    // Toggle visibility
                    docTypesContainer.classList.toggle('hidden');

                    if (isHidden) {
                        // First make it visible, then animate it in
                        docTypesContainer.classList.add('show');
                        docTypesToggleIcon.style.transform = 'rotate(180deg)';
                        setTimeout(() => {
                            docTypesContainer.style.transform = 'scaleY(1)';
                            docTypesContainer.style.opacity = '1';
                        }, 10);
                    } else {
                        // First animate it out, then hide it
                        docTypesContainer.style.transform = 'scaleY(0.95)';
                        docTypesContainer.style.opacity = '0';
                        docTypesToggleIcon.style.transform = 'rotate(0deg)';
                        docTypesContainer.classList.remove('show');

                        setTimeout(() => {
                            docTypesContainer.classList.add('hidden');
                        }, 200);
                    }
                });
            }

            // Enhanced window resize handler
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 640) {
                    // Ensure desktop elements are properly displayed
                    tocContainer.classList.remove('hidden');
                    if (docTypesContainer) {
                        docTypesContainer.classList.add('hidden');
                        docTypesContainer.style.transform = '';
                        docTypesContainer.style.opacity = '';
                    }
                } else {
                    // Reset mobile menu states
                    tocContainer.classList.add('hidden');
                    if (tocToggleIcon) {
                        tocToggleIcon.style.transform = 'rotate(0deg)';
                    }
                }
            });

            // Clear any existing TOC content
            toc.innerHTML = '';

            // Generate enhanced table of contents with active state tracking
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
                tocEntry.dataset.target = heading.id;
                tocEntry.textContent = heading.textContent;

                // Base classes for all TOC entries
                tocEntry.classList.add(
                    'block',
                    'mb-2',
                    'hover:text-primary-600',
                    'dark:hover:text-primary-400',
                    'transition-colors',
                    'truncate',
                    'border-l',
                    'pl-3',
                    'py-1'
                );

                // Add styling based on heading level
                if (level === 0) {
                    tocEntry.classList.add('text-gray-900', 'dark:text-gray-100', 'font-medium', 'border-gray-200', 'dark:border-gray-800');
                } else if (level === 1) {
                    tocEntry.classList.add('text-gray-700', 'dark:text-gray-300', 'pl-5', 'border-gray-200', 'dark:border-gray-800');
                } else {
                    tocEntry.classList.add('text-gray-600', 'dark:text-gray-400', 'pl-7', 'border-gray-200', 'dark:border-gray-800');
                }

                // Clicking on TOC entry scrolls smoothly to the heading
                tocEntry.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href').substring(1);
                    const targetElement = document.getElementById(targetId);

                    if (targetElement) {
                        // Smooth scroll to target with offset for fixed header
                        window.scrollTo({
                            top: targetElement.offsetTop - 90,
                            behavior: 'smooth'
                        });

                        // Update URL hash without scrolling
                        history.pushState(null, null, '#' + targetId);

                        // Highlight target heading temporarily
                        targetElement.classList.add('bg-primary-50', 'dark:bg-primary-900/20');
                        setTimeout(() => {
                            targetElement.classList.remove('bg-primary-50', 'dark:bg-primary-900/20');
                        }, 1500);
                    }
                });

                toc.appendChild(tocEntry);
            });

            // Highlight active TOC item on scroll
            const observerOptions = {
                root: null,
                rootMargin: '-100px 0px -66%',
                threshold: 0
            };

            const headingObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    const id = entry.target.getAttribute('id');
                    const tocItem = document.querySelector(`#toc a[data-target="${id}"]`);

                    if (tocItem) {
                        if (entry.isIntersecting) {
                            // Remove active class from all TOC items
                            document.querySelectorAll('#toc a').forEach(item => {
                                item.classList.remove('toc-active');
                            });

                            // Add active class to current TOC item
                            tocItem.classList.add('toc-active');
                        }
                    }
                });
            }, observerOptions);

            // Observe all headings
            headings.forEach(heading => {
                headingObserver.observe(heading);
            });

            // Show appropriate section if hash in URL
            if (window.location.hash) {
                const targetId = window.location.hash.substring(1);
                const targetHeading = document.getElementById(targetId);
                const tocItem = document.querySelector(`#toc a[data-target="${targetId}"]`);

                if (targetHeading && tocItem) {
                    // Highlight TOC item
                    tocItem.classList.add('toc-active');

                    // Scroll to heading after a short delay to ensure page is loaded
                    setTimeout(() => {
                        window.scrollTo({
                            top: targetHeading.offsetTop - 90,
                            behavior: 'auto'
                        });
                    }, 300);
                }
            } else {
                // If no hash, highlight first TOC item
                const firstTocItem = document.querySelector('#toc a');
                if (firstTocItem) {
                    firstTocItem.classList.add('toc-active');
                }
            }

            // Add click handlers to feedback buttons
            document.querySelectorAll('.feedback-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    // Here you would typically send feedback data to your server
                    alert('Thank you for your feedback! This helps us improve our documentation.');

                    // Visual feedback
                    const parent = this.parentElement;
                    parent.innerHTML = '<span class="text-green-600 dark:text-green-400">Thanks for your feedback!</span>';
                });
            });
        });
    </script>
</x-guest-layout>



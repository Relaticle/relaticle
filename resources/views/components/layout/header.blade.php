<header
    id="main-header"
    class="bg-white/80 dark:bg-black/80 fixed w-full top-0 z-50 transition-all duration-300 border-b border-gray-100 dark:border-white/5 backdrop-blur-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 md:h-20 transition-all duration-300" id="header-container">

            <div class="flex flex-1 items-center">
                <a href="{{ url('/') }}" class="transition-opacity" aria-label="Relaticle Home">
                    <x-brand.logo-lockup size="md" class="text-black dark:text-white" />
                </a>
            </div>

            <div class="hidden md:flex items-center bg-gray-50/50 dark:bg-white/5 border border-gray-200/50 dark:border-white/10 rounded-full px-1 py-1">
                <nav class="flex items-center">
                    <a href="{{ url('/#features') }}"
                       class="px-4 py-1.5 text-gray-700 dark:text-gray-300 hover:text-primary dark:hover:text-primary-400 text-[13px] font-medium transition-colors rounded-full">
                        Features
                    </a>
                    <a href="{{ route('documentation.index') }}"
                       class="px-4 py-1.5 text-gray-700 dark:text-gray-300 hover:text-primary dark:hover:text-primary-400 text-[13px] font-medium transition-colors rounded-full">
                        Documentation
                    </a>

                    <a href="https://github.com/Relaticle/relaticle" target="_blank"
                       class="px-4 py-1.5 text-gray-700 dark:text-gray-300 hover:text-primary dark:hover:text-primary-400 text-[13px] font-medium transition-colors rounded-full flex items-center gap-2">
                        <x-ri-github-fill class="w-4 h-4"/>
                        @if(isset($githubStars) && $githubStars > 0)
                            <span class="opacity-80">{{ $formattedGithubStars }}</span>
                        @else
                            <span>GitHub</span>
                        @endif
                    </a>

                    <a href="{{ route('discord') }}" target="_blank"
                       class="px-4 py-1.5 text-gray-700 dark:text-gray-300 hover:text-primary dark:hover:text-primary-400 text-[13px] font-medium transition-colors rounded-full flex items-center gap-1.5">
                        <x-ri-discord-line class="w-4 h-4"/>
                        <span>Discord</span>
                    </a>
                </nav>
            </div>

            <div class="flex flex-1 items-center justify-end gap-3">

                <a href="{{ route('login') }}"
                   class="flex h-9 items-center rounded-lg border border-gray-200
              bg-white px-5 text-sm font-medium
              transition-all duration-100
              hover:bg-gray-50/50
              dark:border-white/10 dark:bg-black dark:text-white dark:hover:bg-neutral-900">
                    Sign In
                </a>

                <a href="{{ route('register') }}"
                   class="flex h-9 items-center rounded-lg border border-primary
              bg-primary px-5 text-sm font-medium text-white
              transition-all duration-100
              hover:bg-primary/95 hover:shadow-sm hover:ring-2 hover:ring-primary/10">
                    Start for free
                </a>

                <div class="md:hidden">
                    <button id="mobile-menu-button"
                            class="p-2 text-gray-500 hover:text-primary transition-colors duration-200"
                            aria-label="Toggle mobile menu">
                        <x-ri-menu-line class="h-6 w-6"/>
                    </button>
                </div>

            </div>
        </div>
    </div>
</header>

<div class="h-16 md:h-20"></div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const header = document.getElementById('main-header');
        const container = document.getElementById('header-container');

        window.addEventListener('scroll', () => {
            if (window.scrollY > 20) {
                header.classList.add('shadow-sm', 'bg-white/98', 'dark:bg-black/98');
                container.classList.replace('h-20', 'h-14');
            } else {
                header.classList.remove('shadow-sm', 'bg-white/98', 'dark:bg-black/98');
                container.classList.replace('h-14', 'h-20');
            }
        });
    });
</script>

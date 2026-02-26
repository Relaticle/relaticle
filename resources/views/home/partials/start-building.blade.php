<section class="relative bg-[#0c0a1d] overflow-hidden">
    {{-- Gradient mesh --}}
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_80%_60%_at_50%_-20%,rgba(120,80,220,0.15),transparent)] pointer-events-none"></div>
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_60%_50%_at_80%_80%,rgba(80,60,180,0.08),transparent)] pointer-events-none"></div>
    {{-- Curved notch — white tab hanging down with rounded bottom corners --}}
    <div class="absolute top-0 inset-x-0 pointer-events-none" aria-hidden="true">
        <svg class="w-full h-auto" viewBox="0 0 1440 80" fill="none" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M0 0H1440V0H1440C1440 0 1380 0 1340 0C1300 0 1280 40 1240 40H200C160 40 140 0 100 0C60 0 0 0 0 0V0Z"
                  class="fill-white dark:fill-[#0c0a1d]"/>
        </svg>
    </div>

    <div class="relative max-w-xl mx-auto text-center px-6 pt-24 md:pt-32 pb-20 md:pb-28">
        <h2 class="font-display text-3xl sm:text-4xl md:text-[2.75rem] font-bold text-white tracking-[-0.02em] leading-[1.15]">
            Your CRM, Your Rules
        </h2>
        <p class="mt-5 text-base md:text-lg text-gray-400 max-w-sm mx-auto leading-relaxed">
            Self-hosted. Agent-native. Full control over your data and your AI.
        </p>

        <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ route('register') }}"
               class="group flex h-11 items-center gap-2.5 rounded-lg bg-white px-7 text-sm font-semibold text-gray-900 shadow-[0_1px_2px_rgba(0,0,0,0.1)] hover:bg-gray-100 transition-all duration-200">
                <span>Start for free</span>
                <x-ri-arrow-right-line class="h-3.5 w-3.5 transition-transform duration-300 group-hover:translate-x-0.5"/>
            </a>
            <a href="https://github.com/relaticle/relaticle" target="_blank"
               class="group flex h-11 items-center gap-2.5 rounded-lg border border-white/[0.12] bg-white/[0.05] px-7 text-sm font-semibold text-white hover:bg-white/[0.10] hover:border-white/[0.20] transition-all duration-200">
                <x-ri-github-fill class="h-4 w-4"/>
                <span>GitHub</span>
            </a>
        </div>

        <div class="mt-6 flex items-center justify-center gap-1 text-[13px] text-gray-500">
            <span>No credit card</span>
            <span class="mx-1.5 text-gray-600">&middot;</span>
            <span>Deploy in 5 minutes</span>
        </div>
    </div>
</section>

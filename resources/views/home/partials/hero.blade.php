<section class="relative pt-16 pb-20 md:pt-24 md:pb-32 bg-white dark:bg-black overflow-hidden">

    {{-- ═══════════════════════════════════════════
         Background system — layered depth
    ════════════════════════════════════════════ --}}

    {{-- Layer 1: Soft grid --}}
    <div class="absolute inset-0 bg-[linear-gradient(to_right,rgba(0,0,0,0.015)_1px,transparent_1px),linear-gradient(to_bottom,rgba(0,0,0,0.015)_1px,transparent_1px)] dark:bg-[linear-gradient(to_right,rgba(255,255,255,0.025)_1px,transparent_1px),linear-gradient(to_bottom,rgba(255,255,255,0.025)_1px,transparent_1px)] bg-[size:3rem_3rem] [mask-image:radial-gradient(ellipse_70%_50%_at_50%_50%,black_30%,transparent_100%)]"></div>

    {{-- Layer 2: Gradient mesh — large soft blobs --}}
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        {{-- Center primary wash --}}
        <div class="absolute top-[10%] left-1/2 -translate-x-1/2 w-[90%] h-[80%] rounded-full opacity-100">
            <div class="absolute inset-0 bg-gradient-to-b from-primary/[0.04] via-primary/[0.02] to-transparent dark:from-primary/[0.08] dark:via-primary/[0.04] dark:to-transparent rounded-full blur-[100px]"></div>
        </div>
        {{-- Left warm blob --}}
        <div class="absolute top-[20%] -left-[5%] w-[40%] h-[50%] bg-gradient-to-br from-primary/[0.05] via-violet-500/[0.03] to-transparent dark:from-primary/[0.09] dark:via-violet-500/[0.05] dark:to-transparent rounded-full blur-[80px]"></div>
        {{-- Right cool blob --}}
        <div class="absolute top-[15%] -right-[5%] w-[35%] h-[45%] bg-gradient-to-bl from-indigo-500/[0.04] via-primary/[0.02] to-transparent dark:from-indigo-400/[0.07] dark:via-primary/[0.04] dark:to-transparent rounded-full blur-[80px]"></div>
        {{-- Bottom warm accent --}}
        <div class="absolute bottom-[10%] left-1/2 -translate-x-1/2 w-[60%] h-[30%] bg-gradient-to-t from-violet-500/[0.03] to-transparent dark:from-violet-400/[0.06] dark:to-transparent rounded-full blur-[80px]"></div>
    </div>

    {{-- Layer 3: Top radial spotlight --}}
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[600px] h-[400px] bg-[radial-gradient(ellipse_at_center,var(--color-primary-200)_0%,transparent_70%)] opacity-[0.15] dark:opacity-[0.08] pointer-events-none blur-2xl"></div>

    {{-- ═══════════════════════════════════════════
         Content
    ════════════════════════════════════════════ --}}

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="flex flex-col items-center gap-8 md:gap-10">

            {{-- ── Badge ── --}}
            <a href="https://github.com/relaticle/relaticle" target="_blank"
               class="hero-enter hero-enter-1 group relative inline-flex items-center gap-3 rounded-full border border-gray-200/80 dark:border-white/[0.08] bg-white/80 dark:bg-white/[0.03] backdrop-blur-sm pl-4 pr-3 py-1.5 text-xs shadow-[0_1px_3px_rgba(0,0,0,0.04)] hover:shadow-[0_2px_8px_rgba(0,0,0,0.06)] hover:border-gray-300 dark:hover:border-white/[0.15] transition-all duration-300">
                <span class="text-gray-400 dark:text-gray-500 tracking-wide uppercase text-[10px] font-medium">Built with</span>
                <span class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-50/80 dark:bg-white/[0.06] px-2.5 py-0.5 border border-transparent dark:border-white/[0.04]">
                        <x-icon-laravel class="h-3 w-3"/>
                        <span class="font-medium text-gray-700 dark:text-gray-300">Laravel</span>
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-50/80 dark:bg-white/[0.06] px-2.5 py-0.5 border border-transparent dark:border-white/[0.04]">
                        <x-icon-filament class="h-3 w-3 dark:fill-white"/>
                        <span class="font-medium text-gray-700 dark:text-gray-300">Filament</span>
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-50/80 dark:bg-white/[0.06] px-2.5 py-0.5 border border-transparent dark:border-white/[0.04]">
                        <span class="font-medium text-gray-700 dark:text-gray-300">MCP</span>
                    </span>
                </span>
                <x-ri-arrow-right-s-line class="h-3.5 w-3.5 text-gray-300 dark:text-gray-600 group-hover:text-gray-500 dark:group-hover:text-gray-400 group-hover:translate-x-0.5 transition-all duration-300"/>
            </a>

            {{-- ── Heading ── --}}
            <div class="text-center max-w-3xl">
                <h1 class="hero-enter hero-enter-2 font-display text-[2.5rem] sm:text-5xl md:text-[3.5rem] lg:text-6xl font-bold text-gray-950 dark:text-white leading-[1.08] tracking-[-0.02em] text-pretty">
                    The Open-Source CRM<br class="hidden sm:block"/>
                    <span class="relative inline-block mt-1">
                        {{-- Ambient glow --}}
                        <span class="absolute -inset-x-[15%] bottom-[-0.15em] h-[0.7em] rounded-full bg-primary/[0.05] dark:bg-primary/[0.10] blur-2xl pointer-events-none" aria-hidden="true"></span>

                        {{-- Highlight band --}}
                        <span class="absolute bottom-[0.02em] left-[-0.02em] right-[-0.02em] h-[0.34em] rounded-full bg-gradient-to-r from-primary/0 via-primary/[0.08] to-primary/0 dark:via-primary/[0.14] pointer-events-none" aria-hidden="true"></span>

                        {{-- Accent line --}}
                        <span class="absolute -bottom-[2px] left-[8%] right-[8%] h-[2px] rounded-full bg-gradient-to-r from-transparent via-primary/25 to-transparent dark:via-primary/40 pointer-events-none" aria-hidden="true"></span>

                        {{-- Shimmer --}}
                        <span class="absolute -bottom-[2px] left-0 right-0 h-[0.36em] rounded-full overflow-hidden pointer-events-none" aria-hidden="true">
                            <span class="absolute inset-0 bg-[linear-gradient(105deg,transparent_40%,rgba(255,255,255,0.12)_45%,rgba(255,255,255,0.2)_50%,rgba(255,255,255,0.12)_55%,transparent_60%)] dark:bg-[linear-gradient(105deg,transparent_40%,rgba(255,255,255,0.04)_45%,rgba(255,255,255,0.08)_50%,rgba(255,255,255,0.04)_55%,transparent_60%)] bg-[length:250%_100%] animate-[shimmer-sweep_8s_ease-in-out_infinite]"></span>
                        </span>

                        {{-- Text --}}
                        <span class="relative z-10 bg-[linear-gradient(90deg,var(--color-primary-800),var(--color-primary),var(--color-primary-500),var(--color-primary),var(--color-primary-800))] bg-[length:200%_auto] animate-[gradient-shift_14s_ease-in-out_infinite] bg-clip-text text-transparent">Built for AI Agents</span>

                        {{-- Dark mode text glow --}}
                        <span class="absolute inset-0 z-0 bg-[linear-gradient(90deg,var(--color-primary-800),var(--color-primary),var(--color-primary-500),var(--color-primary),var(--color-primary-800))] bg-[length:200%_auto] animate-[gradient-shift_14s_ease-in-out_infinite] bg-clip-text text-transparent blur-2xl opacity-0 dark:opacity-20 select-none pointer-events-none" aria-hidden="true">Built for AI Agents</span>
                    </span>
                </h1>

                <p class="hero-enter hero-enter-3 mt-6 text-base sm:text-lg text-gray-500 dark:text-gray-400 max-w-xl mx-auto leading-relaxed tracking-[-0.01em]">
                    MCP-native. Self-hosted. 20 tools for any AI to operate your CRM.<br class="hidden sm:block"/>
                    Full control over your data and your AI.
                </p>
            </div>

            {{-- ── CTA Buttons ── --}}
            <div class="hero-enter hero-enter-4 flex flex-col sm:flex-row items-center gap-3 mt-2">
                <a href="{{ route('register') }}"
                   class="group relative flex h-11 items-center gap-2.5 rounded-xl bg-primary px-7 text-sm font-semibold text-white shadow-[0_1px_2px_rgba(0,0,0,0.1),inset_0_1px_0_rgba(255,255,255,0.12)] hover:shadow-[0_4px_16px_var(--color-primary-500)/25] hover:brightness-110 transition-all duration-300">
                    <span>Start for free</span>
                    <x-ri-arrow-right-line class="h-3.5 w-3.5 transition-transform duration-300 group-hover:translate-x-0.5"/>
                </a>

                <a href="https://github.com/relaticle/relaticle" target="_blank"
                   class="group flex h-11 items-center gap-2.5 rounded-xl border border-gray-200 bg-white px-7 text-sm font-semibold text-gray-700 shadow-[0_1px_2px_rgba(0,0,0,0.04)] hover:shadow-[0_2px_8px_rgba(0,0,0,0.06)] hover:border-gray-300 transition-all duration-300 dark:border-white/[0.08] dark:bg-white/[0.03] dark:text-white dark:hover:bg-white/[0.06] dark:hover:border-white/[0.15]">
                    <x-ri-github-fill class="h-4 w-4"/>
                    <span>GitHub</span>
                </a>
            </div>

            {{-- ── App Preview with glow ── --}}
            <div class="hero-enter hero-enter-5 relative w-full max-w-5xl mt-8 md:mt-12">

                {{-- Multi-color glow behind mockup --}}
                <div class="absolute -inset-6 md:-inset-12 -z-10 pointer-events-none" aria-hidden="true">
                    <div class="absolute top-[-5%] left-[5%] w-[50%] h-[65%] rounded-full bg-primary/[0.07] dark:bg-primary/[0.14] blur-[80px] animate-[gradient-shift_20s_ease-in-out_infinite]"></div>
                    <div class="absolute top-[0%] right-[5%] w-[45%] h-[55%] rounded-full bg-violet-500/[0.05] dark:bg-violet-500/[0.10] blur-[80px] animate-[gradient-shift_25s_ease-in-out_infinite_reverse]"></div>
                    <div class="absolute bottom-[-5%] left-[15%] w-[55%] h-[50%] rounded-full bg-indigo-500/[0.04] dark:bg-indigo-400/[0.08] blur-[80px] animate-[gradient-shift_22s_ease-in-out_infinite]"></div>
                    <div class="absolute bottom-[0%] right-[10%] w-[35%] h-[40%] rounded-full bg-primary/[0.03] dark:bg-primary/[0.07] blur-[80px]"></div>
                </div>

                {{-- Perspective wrapper --}}
                <div class="[perspective:2000px]">
                    <div class="relative rounded-2xl overflow-hidden border border-gray-200/80 dark:border-white/[0.08] shadow-[0_8px_40px_rgba(0,0,0,0.08),0_1px_3px_rgba(0,0,0,0.04)] dark:shadow-[0_8px_40px_rgba(0,0,0,0.4),0_0_0_1px_rgba(255,255,255,0.05)] transform-gpu hover:[transform:rotateX(1deg)] transition-transform duration-700 ease-out">

                        {{-- Browser chrome --}}
                        <div class="bg-gradient-to-b from-gray-50 to-gray-100/50 dark:from-neutral-900 dark:to-neutral-900/80 border-b border-gray-200/80 dark:border-white/[0.06] px-4 py-3 flex items-center">
                            <div class="flex gap-2">
                                <div class="w-3 h-3 rounded-full bg-[#FF5F57] dark:bg-[#FF5F57]/60"></div>
                                <div class="w-3 h-3 rounded-full bg-[#FEBC2E] dark:bg-[#FEBC2E]/60"></div>
                                <div class="w-3 h-3 rounded-full bg-[#28C840] dark:bg-[#28C840]/60"></div>
                            </div>
                            <div class="ml-4 flex-1 max-w-sm mx-auto bg-white dark:bg-neutral-800 rounded-lg px-3 py-1.5 text-xs text-gray-400 dark:text-gray-500 flex items-center justify-center border border-gray-100 dark:border-white/[0.04] shadow-[inset_0_1px_2px_rgba(0,0,0,0.04)]">
                                <x-ri-lock-line class="h-3 w-3 text-gray-300 dark:text-gray-600 mr-1.5"/>
                                <span>app.relaticle.com</span>
                            </div>
                        </div>

                        {{-- Screenshot --}}
                        <div class="relative">
                            <img id="app-companies-preview-image"
                                 src="{{ asset('images/app-companies-preview.jpg') }}"
                                 alt="Relaticle CRM Dashboard"
                                 class="w-full h-auto"
                                 width="1200"
                                 height="675"
                                 loading="lazy">
                            {{-- Bottom fade overlay --}}
                            <div class="absolute bottom-0 left-0 right-0 h-24 bg-gradient-to-t from-white/60 via-white/20 to-transparent dark:from-black/60 dark:via-black/20 dark:to-transparent pointer-events-none"></div>
                        </div>
                    </div>
                </div>

                {{-- Reflection --}}
                <div class="hidden md:block relative h-16 mt-1 overflow-hidden pointer-events-none opacity-[0.03] dark:opacity-[0.04]" aria-hidden="true">
                    <div class="absolute inset-x-0 top-0 h-32 bg-gradient-to-b from-gray-400 to-transparent rounded-b-2xl blur-sm [transform:scaleY(-1)]"></div>
                </div>
            </div>

            {{-- ── Stats ── --}}
            <div class="hero-enter hero-enter-6 w-full max-w-3xl grid grid-cols-2 md:grid-cols-4 gap-6 md:gap-8 mt-4">
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-primary/[0.06] dark:bg-primary/[0.10] mb-2.5">
                        <x-ri-open-source-line class="w-4 h-4 text-primary dark:text-primary-400"/>
                    </div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-white">Open Source</div>
                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Free to use & customize</div>
                </div>
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-primary/[0.06] dark:bg-primary/[0.10] mb-2.5">
                        <x-ri-robot-2-line class="w-4 h-4 text-primary dark:text-primary-400"/>
                    </div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-white">Agent-Native</div>
                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">MCP server with 20 tools</div>
                </div>
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-primary/[0.06] dark:bg-primary/[0.10] mb-2.5">
                        <x-ri-server-line class="w-4 h-4 text-primary dark:text-primary-400"/>
                    </div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-white">Self-Hosted</div>
                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Own your data & your AI</div>
                </div>
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-primary/[0.06] dark:bg-primary/[0.10] mb-2.5">
                        <x-ri-layout-grid-line class="w-4 h-4 text-primary dark:text-primary-400"/>
                    </div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-white">22 Field Types</div>
                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Customize without code</div>
                </div>
            </div>

        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const appPreviewImage = document.getElementById('app-companies-preview-image');
        const lightImage = "{{ asset('images/app-companies-preview.jpg') }}";
        const darkImage = "{{ asset('images/app-companies-preview-dark.jpg') }}";

        updateImageSource();

        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.attributeName === 'class') {
                    updateImageSource();
                }
            });
        });

        observer.observe(document.documentElement, {attributes: true});

        function updateImageSource() {
            if (document.documentElement.classList.contains('dark')) {
                appPreviewImage.src = darkImage;
            } else {
                appPreviewImage.src = lightImage;
            }
        }
    });
</script>

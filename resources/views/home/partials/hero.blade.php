<section class="relative overflow-hidden bg-white pb-20 pt-14 dark:bg-black sm:pt-20 lg:pb-24">
    <div class="pointer-events-none absolute inset-0 bg-grid-pattern opacity-[0.03] dark:opacity-[0.06]"></div>
    <div class="pointer-events-none absolute -top-28 left-1/2 h-[28rem] w-[28rem] -translate-x-1/2 rounded-full bg-primary/15 blur-3xl dark:bg-primary/25"></div>
    <div class="pointer-events-none absolute bottom-0 left-0 h-72 w-72 rounded-full bg-violet-500/10 blur-3xl dark:bg-violet-500/20"></div>
    <div class="pointer-events-none absolute -right-12 top-40 h-80 w-80 rounded-full bg-cyan-400/10 blur-3xl dark:bg-cyan-400/20"></div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-4xl text-center">
            <div class="flex flex-wrap items-center justify-center gap-3">
                <span
                    class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold tracking-wide text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                    <span class="mr-2 inline-block h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    Built for 2026 growth teams
                </span>

                <span
                    class="inline-flex items-center gap-1 rounded-full border border-gray-200/80 bg-white/80 px-3 py-1 text-xs text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-900/80 dark:text-gray-300">
                    Built with
                    <x-icon-laravel class="h-3.5 w-3.5"/>
                    <span class="font-semibold">Laravel</span>
                    <span class="text-gray-400">·</span>
                    <x-icon-filament class="h-3.5 w-3.5 dark:fill-white"/>
                    <span class="font-semibold">Filament</span>
                </span>
            </div>

            <h1 class="mt-7 font-display text-4xl font-semibold leading-[1.04] tracking-tight text-black dark:text-white sm:text-5xl md:text-6xl lg:text-7xl">
                A calmer, smarter way to run your
                <span
                    class="bg-gradient-to-r from-primary via-violet-500 to-cyan-500 bg-clip-text text-transparent dark:from-primary-300 dark:via-violet-300 dark:to-cyan-300">
                    customer engine
                </span>
            </h1>

            <p class="mx-auto mt-6 max-w-2xl text-base leading-relaxed text-gray-600 dark:text-gray-300 sm:text-lg">
                Relaticle gives modern teams one elegant place to manage leads, companies, and pipeline momentum without
                data lock-in. Open-source, customizable, and designed for focus.
            </p>

            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="{{ route('register') }}"
                   class="group inline-flex w-full items-center justify-center gap-2 rounded-full border border-primary/80 bg-primary px-7 py-3 text-base font-semibold text-white shadow-lg shadow-primary/20 transition-all duration-300 hover:-translate-y-0.5 hover:bg-primary-600 hover:shadow-xl hover:shadow-primary/30 sm:w-auto">
                    <span>Start for free</span>
                    <x-heroicon-c-arrow-right class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1"/>
                </a>

                <a href="https://github.com/Relaticle/relaticle"
                   target="_blank"
                   rel="noopener"
                   class="group inline-flex w-full items-center justify-center gap-2 rounded-full border border-gray-200 bg-white/80 px-7 py-3 text-base font-medium text-gray-700 transition-all duration-300 hover:-translate-y-0.5 hover:border-gray-300 hover:text-black dark:border-gray-700 dark:bg-gray-900/80 dark:text-gray-200 dark:hover:border-gray-600 dark:hover:text-white sm:w-auto">
                    <x-icon-github class="h-5 w-5 transition-transform duration-300 group-hover:scale-110"/>
                    <span>View on GitHub</span>
                </a>
            </div>

            <div class="mx-auto mt-8 grid max-w-3xl grid-cols-1 gap-3 text-left sm:grid-cols-3">
                <div class="rounded-2xl border border-gray-200/70 bg-white/80 px-4 py-3 shadow-sm dark:border-gray-800 dark:bg-gray-900/70">
                    <p class="text-sm font-semibold text-black dark:text-white">Unlimited customization</p>
                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">Custom fields and workflows without code.</p>
                </div>
                <div class="rounded-2xl border border-gray-200/70 bg-white/80 px-4 py-3 shadow-sm dark:border-gray-800 dark:bg-gray-900/70">
                    <p class="text-sm font-semibold text-black dark:text-white">Data ownership first</p>
                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">Self-hosted and open-source under AGPL-3.0.</p>
                </div>
                <div class="rounded-2xl border border-gray-200/70 bg-white/80 px-4 py-3 shadow-sm dark:border-gray-800 dark:bg-gray-900/70">
                    <p class="text-sm font-semibold text-black dark:text-white">Modern by default</p>
                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">Laravel 12, Filament, and a polished UX.</p>
                </div>
            </div>
        </div>

        <div class="relative mx-auto mt-16 max-w-6xl lg:mt-20">
            <div
                class="pointer-events-none absolute -inset-x-6 -top-6 -bottom-6 rounded-[2rem] bg-gradient-to-r from-primary/20 via-violet-500/15 to-cyan-400/20 blur-2xl"></div>

            <div
                class="relative overflow-hidden rounded-2xl border border-gray-200/70 bg-white/85 shadow-2xl shadow-gray-900/10 ring-1 ring-white/60 dark:border-gray-800 dark:bg-gray-950/80 dark:shadow-black/35 dark:ring-gray-800/60">
                <div class="flex items-center gap-3 border-b border-gray-200/70 bg-gray-50/80 px-4 py-2.5 dark:border-gray-800 dark:bg-gray-900/80">
                    <div class="flex space-x-1.5">
                        <div class="h-2.5 w-2.5 rounded-full bg-gray-300/90 dark:bg-gray-600/90"></div>
                        <div class="h-2.5 w-2.5 rounded-full bg-gray-300/90 dark:bg-gray-600/90"></div>
                        <div class="h-2.5 w-2.5 rounded-full bg-gray-300/90 dark:bg-gray-600/90"></div>
                    </div>
                    <div
                        class="ml-2 flex-1 rounded-md border border-gray-200 bg-white/90 px-3 py-1 text-xs text-gray-500 dark:border-gray-700 dark:bg-gray-800/80 dark:text-gray-300">
                        https://app.relaticle.com
                    </div>
                </div>

                <div class="relative">
                    <picture id="app-companies-preview-picture">
                        <source id="preview-source-avif"
                                srcset="{{ asset('images/app-companies-preview.avif') }}"
                                type="image/avif">
                        <source id="preview-source-webp"
                                srcset="{{ asset('images/app-companies-preview.webp') }}"
                                type="image/webp">
                        <img id="app-companies-preview-image"
                             src="{{ asset('images/app-companies-preview.png') }}"
                             alt="Relaticle CRM Dashboard"
                             class="h-auto w-full"
                             width="2880"
                             height="1800"
                             loading="lazy">
                    </picture>
                    <div class="pointer-events-none absolute inset-0 bg-gradient-to-tr from-primary/10 via-transparent to-cyan-400/10"></div>
                </div>
            </div>

            <div
                class="pointer-events-none absolute -left-8 top-12 hidden max-w-[220px] rounded-2xl border border-white/70 bg-white/85 p-4 shadow-xl dark:border-gray-700 dark:bg-gray-900/80 lg:block">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Pipeline signal</p>
                <p class="mt-1 text-sm font-semibold text-black dark:text-white">Everything in one clear view</p>
            </div>
            <div
                class="pointer-events-none absolute -right-8 bottom-12 hidden max-w-[220px] rounded-2xl border border-white/70 bg-white/85 p-4 shadow-xl dark:border-gray-700 dark:bg-gray-900/80 lg:block">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Team rhythm</p>
                <p class="mt-1 text-sm font-semibold text-black dark:text-white">Faster handoffs, fewer missed deals</p>
            </div>
        </div>

        <div class="mx-auto mt-14 grid max-w-5xl grid-cols-2 gap-3 md:grid-cols-4">
            <div class="rounded-2xl border border-gray-200/70 bg-white/80 px-4 py-4 text-center dark:border-gray-800 dark:bg-gray-900/70">
                <p class="text-sm font-semibold text-black dark:text-white">Open Source</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Transparent and community-driven</p>
            </div>
            <div class="rounded-2xl border border-gray-200/70 bg-white/80 px-4 py-4 text-center dark:border-gray-800 dark:bg-gray-900/70">
                <p class="text-sm font-semibold text-black dark:text-white">AI-Ready Workflows</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Built for modern automations</p>
            </div>
            <div class="rounded-2xl border border-gray-200/70 bg-white/80 px-4 py-4 text-center dark:border-gray-800 dark:bg-gray-900/70">
                <p class="text-sm font-semibold text-black dark:text-white">Secure Foundation</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Enterprise-grade architecture</p>
            </div>
            <div class="rounded-2xl border border-gray-200/70 bg-white/80 px-4 py-4 text-center dark:border-gray-800 dark:bg-gray-900/70">
                <p class="text-sm font-semibold text-black dark:text-white">Scales with You</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">From startup to multi-team ops</p>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sourceAvif = document.getElementById('preview-source-avif');
        const sourceWebp = document.getElementById('preview-source-webp');
        const fallbackImg = document.getElementById('app-companies-preview-image');

        if (!sourceAvif || !sourceWebp || !fallbackImg) {
            return;
        }

        const sources = {
            light: {
                avif: "{{ asset('images/app-companies-preview.avif') }}",
                webp: "{{ asset('images/app-companies-preview.webp') }}",
                png: "{{ asset('images/app-companies-preview.png') }}",
            },
            dark: {
                avif: "{{ asset('images/app-companies-preview-dark.avif') }}",
                webp: "{{ asset('images/app-companies-preview-dark.webp') }}",
                png: "{{ asset('images/app-companies-preview-dark.png') }}",
            },
        };

        const updateImageSource = function () {
            const theme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
            sourceAvif.srcset = sources[theme].avif;
            sourceWebp.srcset = sources[theme].webp;
            fallbackImg.src = sources[theme].png;
        };

        updateImageSource();

        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.attributeName === 'class') {
                    updateImageSource();
                }
            });
        });
        observer.observe(document.documentElement, {attributes: true, attributeFilter: ['class']});

        window.addEventListener('theme-changed', updateImageSource);
    });
</script>

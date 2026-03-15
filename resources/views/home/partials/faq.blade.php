@php
    $faqs = [
        ['Is Relaticle production-ready?', 'Yes. Relaticle has 1,100+ automated tests, 5-layer authorization, 56+ MCP-specific tests, and is used in production. The codebase is continuously tested with PHPStan static analysis and Pest mutation testing.'],
        ['What AI agents can I connect?', 'Any agent that speaks MCP (Model Context Protocol). Claude, ChatGPT, Gemini, open-source models, or your own custom agents. Relaticle\'s MCP server provides 20 tools for AI agents to read, create, update, and delete CRM data.'],
        ['What is MCP?', 'MCP (Model Context Protocol) is an open standard that lets AI agents interact with tools and data sources. Relaticle\'s MCP server gives agents 20 tools to work with your CRM data — listing companies, creating contacts, updating opportunities, and more.'],
        ['How is Relaticle different from HubSpot or Salesforce?', 'Relaticle is self-hosted (you own your data), open-source (AGPL-3.0), has 20 MCP tools (vs HubSpot\'s 9), and has no per-seat pricing. It\'s designed for teams who want AI agent integration without vendor lock-in.'],
        ['How do I deploy Relaticle?', 'Deploy with Docker Compose, Laravel Forge, or any PHP 8.4+ hosting with PostgreSQL. Self-hosted means your data never leaves your server. A managed hosting option is also available at app.relaticle.com.'],
        ['Can I customize the data model?', 'Yes. Relaticle offers 22 custom field types including text, email, phone, currency, date, select, multiselect, entity relationships, conditional visibility, and per-field encryption. No migrations or code changes needed.'],
    ];
@endphp

<section id="faq" class="py-24 md:py-32 bg-gray-50 dark:bg-gray-950 relative overflow-hidden">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_1px_1px,rgb(0_0_0/0.04)_1px,transparent_0)] dark:bg-[radial-gradient(circle_at_1px_1px,rgb(255_255_255/0.035)_1px,transparent_0)] bg-[size:24px_24px] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_50%,black_25%,transparent_100%)]"></div>

    <!-- Bottom gradient fade into next section -->
    <div class="absolute bottom-0 left-0 right-0 h-32 bg-gradient-to-b from-transparent to-white dark:to-black pointer-events-none"></div>

    <div class="relative max-w-3xl mx-auto px-6 lg:px-8">
        <div class="max-w-2xl mx-auto text-center mb-16">
            <h2 class="font-display text-3xl sm:text-4xl md:text-[2.75rem] font-bold text-gray-950 dark:text-white tracking-[-0.02em] leading-[1.15]">
                Frequently Asked Questions
            </h2>
            <p class="mt-5 text-base md:text-lg text-gray-500 dark:text-gray-400 max-w-lg mx-auto leading-relaxed">
                Everything you need to know about Relaticle, from deployment to AI agent integration.
            </p>
        </div>

        <div x-data="{ open: null }" class="divide-y divide-gray-200/80 dark:divide-white/[0.06]">
            @foreach($faqs as $index => [$question, $answer])
                <div class="faq-item py-5">
                    <button @click="open = open === {{ $index }} ? null : {{ $index }}"
                            class="flex w-full items-center justify-between text-left gap-4 cursor-pointer hover:text-primary dark:hover:text-primary-400 transition-colors duration-150">
                        <span class="text-base font-semibold text-gray-900 dark:text-white">
                            {{ $question }}
                        </span>
                        <x-ri-arrow-down-s-line class="h-5 w-5 shrink-0 text-gray-400 transition-transform duration-200" ::class="open === {{ $index }} ? 'rotate-180' : ''"/>
                    </button>
                    <div x-show="open === {{ $index }}" x-collapse class="mt-3 text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                        {{ $answer }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var e = [0.22, 1, 0.36, 1];
            document.querySelectorAll('.faq-item').forEach(function(item) { item.style.opacity = '0'; });
            inView('#faq .divide-y', function() {
                animate('.faq-item', { opacity: [0, 1], y: [20, 0] }, { delay: stagger(0.08), duration: 0.5, ease: e });
            }, { amount: 0.15 });
        });
    </script>
</section>

@php
    /** @var array<string, mixed> $context */
    $company   = $context['company'];
    $portfolio = $context['portfolio_context'];
    $prompt    = $context['narrative_prompt'];

    $bandColors = ['low' => 'text-green-600', 'medium' => 'text-amber-600', 'high' => 'text-red-600'];
    $bandColor  = $bandColors[$company['risk_band']] ?? 'text-gray-600';
@endphp

<div class="space-y-4 text-sm py-1">

    {{-- Company snapshot --}}
    <div class="grid grid-cols-2 gap-3 rounded-lg bg-gray-50 dark:bg-gray-800/50 p-3 text-xs">
        <div>
            <p class="font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Concentration</p>
            <p class="mt-0.5 text-base font-semibold {{ $bandColor }}">
                {{ $company['concentration_percentage'] !== null ? number_format($company['concentration_percentage'], 1).'%' : '—' }}
            </p>
        </div>
        <div>
            <p class="font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Risk Band</p>
            <p class="mt-0.5 font-semibold {{ $bandColor }}">{{ $company['risk_band_label'] }}</p>
        </div>
        <div>
            <p class="font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Partner Source</p>
            <p class="mt-0.5 text-gray-800 dark:text-gray-200">{{ $company['partner_source_label'] ?? '—' }}</p>
        </div>
        <div>
            <p class="font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Geography</p>
            <p class="mt-0.5 text-gray-800 dark:text-gray-200">{{ $company['geography'] ?? '—' }}</p>
        </div>
    </div>

    {{-- Portfolio context --}}
    <div>
        <p class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Portfolio Context</p>
        <dl class="space-y-1 text-xs text-gray-600 dark:text-gray-400">
            <div class="flex justify-between">
                <dt>Portfolio average concentration</dt>
                <dd class="font-medium text-gray-800 dark:text-gray-200">{{ number_format($portfolio['portfolio_average_concentration'], 1) }}%</dd>
            </div>
            @if ($portfolio['concentration_rank'] !== null)
            <div class="flex justify-between">
                <dt>Concentration rank</dt>
                <dd class="font-medium text-gray-800 dark:text-gray-200">#{{ $portfolio['concentration_rank'] }} of {{ $portfolio['total_accounts_with_concentration'] }}</dd>
            </div>
            <div class="flex justify-between">
                <dt>Percentile</dt>
                <dd class="font-medium {{ $bandColor }}">{{ $portfolio['concentration_percentile'] }}th</dd>
            </div>
            @endif
            <div class="flex justify-between">
                <dt>Accounts in same risk band</dt>
                <dd class="font-medium text-gray-800 dark:text-gray-200">{{ $portfolio['accounts_in_same_risk_band'] }}</dd>
            </div>
            @if ($portfolio['accounts_with_same_partner_source'] !== null)
            <div class="flex justify-between">
                <dt>Accounts with same partner source</dt>
                <dd class="font-medium text-gray-800 dark:text-gray-200">{{ $portfolio['accounts_with_same_partner_source'] }}</dd>
            </div>
            @endif
        </dl>
    </div>

    {{-- Narrative prompt (for use with AI) --}}
    <div>
        <p class="font-semibold text-gray-700 dark:text-gray-300 mb-1 flex items-center gap-1">
            <x-heroicon-o-sparkles class="h-4 w-4 text-violet-500" />
            AI Narrative Prompt
        </p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">
            Copy this prompt into your AI assistant to generate a risk narrative.
        </p>
        <pre class="rounded bg-gray-100 dark:bg-gray-800 p-2 text-xs text-gray-700 dark:text-gray-300 whitespace-pre-wrap break-words">{{ $prompt }}</pre>
    </div>

</div>

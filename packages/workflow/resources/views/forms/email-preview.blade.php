@php
    $to = $getState() ?? '';
    $subject = data_get($this->data ?? [], 'subject', '');
    $body = data_get($this->data ?? [], 'body', '');

    // Highlight {{variable}} placeholders
    $highlightVars = function ($text) {
        return preg_replace(
            '/\{\{([^}]+)\}\}/',
            '<span class="inline-block px-1 py-0.5 rounded text-[11px] font-medium bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-300 font-mono">${1}</span>',
            e($text)
        );
    };
@endphp

<div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-800 mt-1">
    {{-- Email header --}}
    <div class="px-3 py-2 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-2 text-[11px] text-gray-500 dark:text-gray-400">
            <span class="font-medium text-gray-400 dark:text-gray-500 w-8">To:</span>
            <span class="text-gray-700 dark:text-gray-300">
                @if($to = data_get($this->data ?? [], 'to', ''))
                    {!! $highlightVars($to) !!}
                @else
                    <span class="italic text-gray-300 dark:text-gray-600">recipient@example.com</span>
                @endif
            </span>
        </div>
        <div class="flex items-center gap-2 text-[11px] mt-1">
            <span class="font-medium text-gray-400 dark:text-gray-500 w-8">Subj:</span>
            <span class="font-semibold text-gray-800 dark:text-gray-200 text-xs">
                @if($subject)
                    {!! $highlightVars($subject) !!}
                @else
                    <span class="italic text-gray-300 dark:text-gray-600 font-normal">No subject</span>
                @endif
            </span>
        </div>
    </div>

    {{-- Email body --}}
    <div class="px-3 py-3 text-xs text-gray-700 dark:text-gray-300 min-h-[60px] leading-relaxed whitespace-pre-wrap break-words">
        @if($body)
            {!! $highlightVars($body) !!}
        @else
            <span class="italic text-gray-300 dark:text-gray-600">Email body will appear here...</span>
        @endif
    </div>
</div>

@props([
    'prompts' => [],
])

@if(count($prompts) > 0)
    <div class="flex flex-wrap gap-2">
        @foreach($prompts as $prompt)
            <button
                type="button"
                x-on:click="$dispatch('send-prompt', { message: '{{ addslashes($prompt['prompt']) }}' })"
                class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-primary-50 hover:text-primary-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-primary-900/20 dark:hover:text-primary-400"
            >
                {{ $prompt['label'] }}
            </button>
        @endforeach
    </div>
@endif

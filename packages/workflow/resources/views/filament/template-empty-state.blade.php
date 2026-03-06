<div class="flex flex-col items-center py-8 px-6">
    <div class="mb-6 text-center">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-primary-50 dark:bg-primary-500/10 mb-3">
            <x-heroicon-o-bolt class="w-6 h-6 text-primary-500" />
        </div>
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Get started with a template</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Pick a template below to create your first workflow, or start from scratch.
        </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 w-full max-w-2xl mb-6">
        @forelse($templates as $template)
            <button
                type="button"
                wire:click="createFromTemplate('{{ $template->id }}')"
                class="flex items-start gap-3 p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-sm transition-all text-left group"
            >
                <div class="flex-shrink-0 w-9 h-9 rounded-lg flex items-center justify-center
                    {{ match($template->category) {
                        'Sales' => 'bg-blue-50 dark:bg-blue-500/10 text-blue-500',
                        'Marketing' => 'bg-purple-50 dark:bg-purple-500/10 text-purple-500',
                        'Support' => 'bg-green-50 dark:bg-green-500/10 text-green-500',
                        'Operations' => 'bg-amber-50 dark:bg-amber-500/10 text-amber-500',
                        default => 'bg-gray-50 dark:bg-gray-500/10 text-gray-500',
                    } }}
                ">
                    @if($template->icon)
                        <x-dynamic-component :component="$template->icon" class="w-5 h-5" />
                    @else
                        <x-heroicon-o-bolt class="w-5 h-5" />
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <span class="block text-sm font-medium text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400">
                        {{ $template->name }}
                    </span>
                    @if($template->description)
                        <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">
                            {{ $template->description }}
                        </span>
                    @endif
                    <span class="inline-block mt-1 text-[10px] font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">
                        {{ $template->category ?? 'General' }}
                    </span>
                </div>
            </button>
        @empty
            <div class="col-span-2 text-center py-4 text-sm text-gray-400">
                No templates available yet.
            </div>
        @endforelse
    </div>

    <div class="flex items-center gap-3">
        <span class="text-xs text-gray-400">or</span>
        <a
            href="{{ $createUrl }}"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300"
        >
            <x-heroicon-o-plus class="w-4 h-4" />
            Create from scratch
        </a>
    </div>
</div>

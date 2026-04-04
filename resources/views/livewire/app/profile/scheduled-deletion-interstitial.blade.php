<div class="flex min-h-screen items-center justify-center bg-gray-50 dark:bg-gray-900 px-4">
    <div class="w-full max-w-md text-center">
        <div class="rounded-xl bg-white dark:bg-gray-800 p-8 shadow-lg ring-1 ring-gray-950/5 dark:ring-white/10">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-danger-100 dark:bg-danger-900/20">
                <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-danger-600 dark:text-danger-400" />
            </div>

            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">
                Account Scheduled for Deletion
            </h2>

            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Your account is scheduled for permanent deletion on
                <strong class="text-gray-950 dark:text-white">{{ auth()->user()->scheduled_deletion_at->format('F j, Y') }}</strong>.
            </p>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                All your data will be permanently removed after this date.
            </p>

            <div class="mt-6 space-y-3">
                {{ $this->cancelDeletionAction }}
                <div>
                    {{ $this->logoutAction }}
                </div>
            </div>
        </div>
    </div>

    <x-filament-actions::modals />
</div>
